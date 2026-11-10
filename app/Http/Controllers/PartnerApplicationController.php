<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use App\Services\DuplicateSpotFinder;
use App\Services\FieldTranslator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Staff-only pages for the partner application review queue.
// The auth and staff middleware in routes/web.php run before any of these methods.
class PartnerApplicationController extends Controller
{
    // GET /admin/applications?state=submitted
    // Lists applications in one state, oldest first, 20 per page.
    public function index(Request $request): View
    {
        // tryFrom() gives null for a missing or unknown state, so we fall back to Submitted.
        $state = ApplicationState::tryFrom($request->string('state')->toString())
            ?? ApplicationState::Submitted;

        $applications = PartnerApplication::with('claimedBy')
            ->where('state', $state)
            ->oldest()
            ->paginate(20)
            ->withQueryString();

        // How many applications are in each state, for the tab labels.
        // toBase() skips the enum cast, so the keys stay plain strings like 'submitted'.
        $counts = PartnerApplication::query()
            ->toBase()
            ->selectRaw('state, count(*) as total')
            ->groupBy('state')
            ->pluck('total', 'state');

        return view('admin.applications.index', [
            'applications' => $applications,
            'state' => $state,
            'states' => ApplicationState::cases(),
            'counts' => $counts,
        ]);
    }

    // GET /admin/applications/{application}
    // Shows everything the shop sent in, plus any existing spots that look like the same place.
    // Laravel finds the row from the id (404 if missing) and builds the finder for us.
    public function show(PartnerApplication $application, DuplicateSpotFinder $duplicates, FieldTranslator $translator): View
    {
        $application->load(['claimedBy', 'spot', 'stateChanges.user', 'translations']);

        // Staff reading in English get the shop's free text in English too.
        // If Google can't help right now, the page still opens, in Japanese.
        $machineTranslated = app()->getLocale() !== 'ja';
        $translationMissing = $machineTranslated && ! $translator->ensure($application, app()->getLocale());

        return view('admin.applications.show', [
            'application' => $application,
            'duplicates' => $duplicates->findFor($application),
            'machineTranslated' => $machineTranslated,
            'translationMissing' => $translationMissing,
        ]);
    }

    // POST /admin/applications/{application}/claim
    // Takes an application for review. The version from the page must still match the row,
    // so if two staff click Claim at the same moment, only one of them gets it.
    public function claim(Request $request, PartnerApplication $application): RedirectResponse
    {
        $request->validate(['version' => ['required', 'integer']]);

        // The enum decides which moves are allowed (only Submitted can go to In review).
        if (! $application->state->canMoveTo(ApplicationState::InReview)) {
            return back()->withErrors(['application' => __('applications.errors.not_waiting')]);
        }

        // One UPDATE that only matches if nobody changed the row since the page was loaded,
        // plus a history row, both in one transaction so neither can happen without the other.
        $changed = DB::transaction(function () use ($request, $application) {
            $changed = PartnerApplication::whereKey($application->getKey())
                ->where('version', $request->integer('version'))
                ->increment('version', 1, [
                    'state' => ApplicationState::InReview->value,
                    'claimed_by' => $request->user()->id,
                    'claimed_at' => now(),
                ]);

            if ($changed === 1) {
                $application->stateChanges()->create([
                    'user_id' => $request->user()->id,
                    'from_state' => $application->state,
                    'to_state' => ApplicationState::InReview,
                ]);
            }

            return $changed;
        });

        if ($changed === 0) {
            return back()->withErrors(['application' => __('applications.errors.changed')]);
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('status', __('applications.flash.claimed'));
    }

    // POST /admin/applications/{application}/decide
    // Approves, sends back or rejects an application. Only the person who claimed it can decide,
    // sending back or rejecting needs a reason, and the version check stops two decisions at once.
    public function decide(Request $request, PartnerApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'integer'],
            'decision' => ['required', Rule::in([
                ApplicationState::Approved->value,
                ApplicationState::Returned->value,
                ApplicationState::Rejected->value,
            ])],
            'reason' => ['nullable', 'required_unless:decision,approved', Rule::in(config('tavnit.review.reasons'))],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:reason,other'],
        ]);

        $next = ApplicationState::from($data['decision']);

        if (! $application->claimedBy?->is($request->user())) {
            return back()->withErrors(['application' => __('applications.errors.not_reviewer')]);
        }

        if (! $application->state->canMoveTo($next)) {
            return back()->withErrors(['application' => __('applications.errors.cannot_decide')]);
        }

        $changed = DB::transaction(function () use ($request, $application, $data, $next) {
            $changed = PartnerApplication::whereKey($application->getKey())
                ->where('version', $data['version'])
                ->increment('version', 1, ['state' => $next->value]);

            if ($changed === 1) {
                $application->stateChanges()->create([
                    'user_id' => $request->user()->id,
                    'from_state' => $application->state,
                    'to_state' => $next,
                    'reason' => $data['reason'] ?? null,
                    'note' => $data['note'] ?? null,
                ]);
            }

            return $changed;
        });

        if ($changed === 0) {
            return back()->withErrors(['application' => __('applications.errors.changed')]);
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('status', __('applications.flash.decided', ['state' => $next->label()]));
    }

    // POST /admin/applications/{application}/publish
    // Turns an approved application into a refill spot that the app can show.
    // If it looks like an existing spot, staff must tick a box to publish anyway.
    public function publish(Request $request, PartnerApplication $application, DuplicateSpotFinder $duplicates): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'integer'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ]);

        if (! $application->state->canMoveTo(ApplicationState::Published)) {
            return back()->withErrors(['application' => __('applications.errors.not_approved')]);
        }

        if ($duplicates->findFor($application)->isNotEmpty() && ! $request->boolean('confirm_duplicate')) {
            return back()->withErrors(['confirm_duplicate' => __('applications.errors.confirm_duplicate')]);
        }

        // Lock the row, check nothing changed, then create the spot, update the application
        // and add the history row together. If any part fails, none of it is saved.
        $spot = DB::transaction(function () use ($request, $application, $data) {
            $current = PartnerApplication::whereKey($application->getKey())->lockForUpdate()->first();

            if ((int) $current->version !== (int) $data['version'] || $current->state !== ApplicationState::Approved) {
                return null;
            }

            $spot = RefillSpot::create([
                'name' => self::tidyName($current->store_name),
                'name_kana' => $current->store_name_kana,
                'name_en' => $current->store_name_en,
                'description_ja' => $current->description_ja,
                'latitude' => $current->latitude,
                'longitude' => $current->longitude,
                'is_active' => true,
            ]);

            $current->forceFill([
                'state' => ApplicationState::Published,
                'refill_spot_id' => $spot->id,
                'version' => $current->version + 1,
            ])->save();

            $current->stateChanges()->create([
                'user_id' => $request->user()->id,
                'from_state' => ApplicationState::Approved,
                'to_state' => ApplicationState::Published,
            ]);

            return $spot;
        });

        if ($spot === null) {
            return back()->withErrors(['application' => __('applications.errors.changed')]);
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('status', __('applications.flash.published', ['name' => $spot->name]));
    }

    // Removes spaces at both ends, including the full-width space (U+3000).
    private static function tidyName(string $name): string
    {
        return preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $name);
    }
}
