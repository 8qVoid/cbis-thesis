<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Models\Facility;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FacilityController extends Controller
{
    use LogsAudit;

    public function index(): View
    {
        $facilities = Facility::query()->latest()->paginate(15);

        return view('facilities.index', compact('facilities'));
    }

    public function create(): View
    {
        return view('facilities.create');
    }

    public function store(StoreFacilityRequest $request): RedirectResponse
    {
        $facility = Facility::create($request->validated());
        $this->logAudit('facility.created', $facility, $request->validated(), $request);

        return redirect()->route('facilities.index')->with('success', 'Facility created.');
    }

    public function show(Facility $facility): View
    {
        return view('facilities.show', compact('facility'));
    }

    public function edit(Facility $facility): View
    {
        return view('facilities.edit', compact('facility'));
    }

    public function update(UpdateFacilityRequest $request, Facility $facility): RedirectResponse
    {
        $facility->update($request->validated());
        $this->logAudit('facility.updated', $facility, $request->validated(), $request);

        return redirect()->route('facilities.index')->with('success', 'Facility updated.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $facility->delete();
        $this->logAudit('facility.deleted', $facility);

        return redirect()->route('facilities.index')->with('success', 'Facility deleted.');
    }
}
