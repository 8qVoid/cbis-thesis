@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="cbis-page-title mb-0">Donors</h1>
        <p class="cbis-page-subtitle">Manage donor profiles and facility association.</p>
    </div>
    <a href="{{ route('donors.create') }}" class="btn btn-danger">Add Donor</a>
</div>
<div class="table-responsive">
    <table class="table table-striped bg-white">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Blood Type</th>
                <th>Facility</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donors as $donor)
                <tr>
                    <td>#{{ $donor->id }}</td>
                    <td>{{ $donor->full_name }}</td>
                    <td>{{ $donor->blood_type }}</td>
                    <td>{{ $donor->facility->name ?? '-' }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('donors.show', $donor) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        <a href="{{ route('donors.edit', $donor) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('donors.destroy', $donor) }}" class="d-inline donor-delete-form" id="donor-delete-{{ $donor->id }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger js-open-delete-modal"
                                data-form-id="donor-delete-{{ $donor->id }}"
                                data-donor-name="{{ $donor->full_name }}"
                            >
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
{{ $donors->links() }}

<div class="modal fade" id="deleteDonorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Donor Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to delete this donor record?</p>
                <p class="mb-1"><strong id="deleteDonorName">-</strong></p>
                <p class="text-danger mb-0"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteDonorBtn">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modalElement = document.getElementById('deleteDonorModal');
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    const nameField = document.getElementById('deleteDonorName');
    const confirmBtn = document.getElementById('confirmDeleteDonorBtn');
    let targetFormId = null;

    document.querySelectorAll('.js-open-delete-modal').forEach((button) => {
        button.addEventListener('click', () => {
            targetFormId = button.getAttribute('data-form-id');
            const donorName = button.getAttribute('data-donor-name') || 'Selected donor';
            nameField.textContent = donorName;
            modal.show();
        });
    });

    confirmBtn.addEventListener('click', () => {
        if (!targetFormId) return;
        const form = document.getElementById(targetFormId);
        if (form) form.submit();
    });
})();
</script>
@endpush
