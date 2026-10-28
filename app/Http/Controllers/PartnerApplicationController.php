<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationState;
use App\Models\PartnerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    // Shows everything the shop sent in. Laravel finds the row from the id (404 if missing).
    public function show(PartnerApplication $application): View
    {
        return view('admin.applications.show', [
            'application' => $application->load('claimedBy'),
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

        // One UPDATE that only matches if nobody changed the row since the page was loaded.
        // increment() adds 1 to version, sets the other columns, and updates updated_at.
        $changed = PartnerApplication::whereKey($application->getKey())
            ->where('version', $request->integer('version'))
            ->increment('version', 1, [
                'state' => ApplicationState::InReview->value,
                'claimed_by' => $request->user()->id,
                'claimed_at' => now(),
            ]);

        if ($changed === 0) {
            return back()->withErrors(['application' => 'Someone else changed this application first. Reload the page to see the latest.']);
        }

        return redirect()->route('admin.applications.show', $application)
            ->with('status', 'You are now reviewing this application.');
    }
}
