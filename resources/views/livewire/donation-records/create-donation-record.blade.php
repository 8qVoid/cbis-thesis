<div>
    <h4>Add Donation Record</h4>

    <form wire:submit.prevent="save" class="card card-body">
        <div class="row g-3">
            @if($isCentralAdmin)
                <div class="col-md-4">
                    <label class="form-label">Facility</label>
                    <select wire:model="facility_id" class="form-select">
                        @foreach($facilities as $facility)
                            <option value="{{ $facility['id'] }}">{{ $facility['name'] }}</option>
                        @endforeach
                    </select>
                    @error('facility_id') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            @endif

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
                    <span wire:loading.remove>Save</span>
                    <span wire:loading>Saving...</span>
                </button>
            </div>
        </div>
    </form>
</div>
