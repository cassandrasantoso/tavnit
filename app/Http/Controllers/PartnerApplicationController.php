<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use App\Services\DuplicateSpotFinder;
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
    public function show(PartnerApplication $application, DuplicateSpotFinder $duplicates): View
    {
        return view('admin.applications.show', [
            'application' => $application->load(['claimedBy', 'stateChanges.user']),
            'duplicates' => $duplicates->findFor($application),
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
            return back()->withErrors(['application' => 'This application is not waiting for a reviewer any more.']);
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
            return back()->withErrors(['application' => 'Someone else changed this application first. Reload the page to see the latest.']);
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('status', 'You are now reviewing this application.');
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
            return back()->withErrors(['application' => 'Only the staff member reviewing this application can decide on it.']);
        }

        if (! $application->state->canMoveTo($next)) {
            return back()->withErrors(['application' => "This application can't be decided right now."]);
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
            return back()->withErrors(['application' => 'Someone else changed this application first. Reload the page to see the latest.']);
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('status', "Decision saved: {$next->label()}.");
    }
}
