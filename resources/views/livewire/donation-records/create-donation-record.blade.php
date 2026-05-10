<div>
    <div class="mb-3">
        <h1 class="cbis-page-title mb-0">Add Donation Record</h1>
        <p class="cbis-page-subtitle">Record a completed facility donation. Once saved, the system adds the collected blood to inventory.</p>
    </div>

    <form wire:submit.prevent="save" class="card card-body">
        <div class="row g-3">
            @if($isCentralAdmin)
                <div class="col-md-4">
                    <label class="form-label">Facility</label>
                    <select wire:model.live="facility_id" class="form-select">
                        @foreach($facilities as $facility)
                            <option value="{{ $facility['id'] }}">{{ $facility['name'] }}</option>
                        @endforeach
                    </select>
                    @error('facility_id') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            @endif

            <div class="col-12">
                <div class="border rounded p-3 bg-light">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Event Registration Lookup</label>
                            <select wire:model.live="selected_event_id" class="form-select">
                                <option value="">Select an event with registered donors</option>
                                @foreach($events as $event)
                                    <option value="{{ $event['id'] }}">
                                        {{ $event['title'] }} @if($event['date']) ({{ $event['date'] }}) @endif - {{ $event['registered_count'] }} registered
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Use this when the donor says they registered for an event.</small>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Search Donor Name</label>
                            <input wire:model.live.debounce.300ms="donor_search" class="form-control" placeholder="Type donor name to find an existing record">
                            @if($matchingDonors)
                                <div class="list-group mt-2">
                                    @foreach($matchingDonors as $match)
                                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" wire:click="selectDonor({{ $match['id'] }})">
                                            <span>{{ $match['name'] }}</span>
                                            <span class="badge text-bg-secondary">{{ $match['blood_type'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($selected_event_id)
                        <div class="mt-3">
                            <div class="fw-semibold mb-2">Registered donors for selected event</div>
                            @if($registeredDonors)
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Blood Type</th>
                                                <th>Registered At</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($registeredDonors as $registeredDonor)
                                                <tr>
                                                    <td>{{ $registeredDonor['name'] }}</td>
                                                    <td>{{ $registeredDonor['blood_type'] }}</td>
                                                    <td>{{ $registeredDonor['registered_at'] }}</td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="selectDonor({{ $registeredDonor['id'] }})">
                                                            Use Donor
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted small">No registered donors found for this event.</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Donor</label>
                <select wire:model.live="donor_id" class="form-select" required>
                    @foreach($donors as $donor)
                        <option value="{{ $donor['id'] }}">{{ $donor['name'] }}</option>
                    @endforeach
                </select>
                @error('donor_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Donation No</label>
                <input wire:model="donation_no" class="form-control" readonly>
                <small class="text-muted">System generated and cannot be edited.</small>
                @error('donation_no') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Donated At</label>
                <input wire:model="donated_at" type="datetime-local" class="form-control" required>
                @error('donated_at') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Blood Type</label>
                <select wire:model="blood_type" class="form-select">
                    @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                @error('blood_type') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Volume (ml)</label>
                <input wire:model="volume_ml" type="number" class="form-control" required>
                @error('volume_ml') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Expiration Date</label>
                <input wire:model="expiration_date" type="date" class="form-control" required>
                @error('expiration_date') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select wire:model="status" class="form-select">
                    <option value="pending">pending</option>
                    <option value="verified">verified</option>
                    <option value="rejected">rejected</option>
                </select>
                @error('status') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-12">
                <button class="btn btn-danger" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save Donation Record</span>
                    <span wire:loading>Saving...</span>
                </button>
            </div>
        </div>
    </form>
</div>
