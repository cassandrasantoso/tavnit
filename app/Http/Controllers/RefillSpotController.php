<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRefillSpotRequest;
use App\Http\Requests\UpdateRefillSpotRequest;
use App\Models\RefillSpot;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// Staff-only pages for managing refill spots.
// The auth and staff middleware in routes/web.php run before any of these methods.
class RefillSpotController extends Controller
{
    // GET /admin/refill-spots
    // Shows every spot, 20 per page, sorted by name.
    public function index(): View
    {
        // withTrashed() keeps removed spots in the list so staff can restore them.
        $spots = RefillSpot::withTrashed()->orderBy('name')->paginate(20);

        return view('admin.refill-spots.index', ['spots' => $spots]);
    }

    // GET /admin/refill-spots/create
    // Shows an empty form. The new, unsaved spot starts with "Show in the app" ticked.
    public function create(): View
    {
        return view('admin.refill-spots.create', [
            'spot' => new RefillSpot(['is_active' => true]),
        ]);
    }

    // POST /admin/refill-spots
    // Saves a new spot from the add form.
    // StoreRefillSpotRequest checks the input first; if it fails, Laravel sends
    // staff back to the form with the errors and what they typed.
    public function store(StoreRefillSpotRequest $request): RedirectResponse
    {
        // validated() returns only the fields the rules allow, so extra fields are ignored.
        $spot = RefillSpot::create($request->validated());

        return redirect()->route('admin.refill-spots.index')
            ->with('status', "Added {$spot->name}.");
    }

    // GET /admin/refill-spots/{refill_spot}/edit
    // Shows the form filled in with this spot's details.
    // Laravel finds the spot from the id in the URL (404 if missing or removed).
    public function edit(RefillSpot $refillSpot): View
    {
        return view('admin.refill-spots.edit', ['spot' => $refillSpot]);
    }

    // PUT /admin/refill-spots/{refill_spot}
    // Saves changes from the edit form, after UpdateRefillSpotRequest checks them.
    public function update(UpdateRefillSpotRequest $request, RefillSpot $refillSpot): RedirectResponse
    {
        $refillSpot->update($request->validated());

        return redirect()->route('admin.refill-spots.index')
            ->with('status', "Saved {$refillSpot->name}.");
    }

    // DELETE /admin/refill-spots/{refill_spot}
    // Removes a spot from the app without deleting it from the database.
    public function destroy(RefillSpot $refillSpot): RedirectResponse
    {
        // Soft delete: fills in deleted_at, the row stays in the table.
        $refillSpot->delete();

        return redirect()->route('admin.refill-spots.index')
            ->with('status', "Removed {$refillSpot->name}. You can restore it from the list.");
    }

    // POST /admin/refill-spots/{refill_spot}/restore
    // Brings a removed spot back by clearing deleted_at.
    // The route has ->withTrashed(), so Laravel can find a removed spot here.
    public function restore(RefillSpot $refillSpot): RedirectResponse
    {
        $refillSpot->restore();

        return redirect()->route('admin.refill-spots.index')
            ->with('status', "Restored {$refillSpot->name}.");
    }
}
