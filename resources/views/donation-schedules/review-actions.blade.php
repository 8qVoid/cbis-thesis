<form
    method="POST"
    action="{{ route('donation-schedules.review',$schedule) }}"
    class="d-inline js-confirm-action"
    data-confirm-title="Approve event?"
    data-confirm-message="This will approve {{ $schedule->title }} and make it available for public listing when it has a valid map location."
    data-confirm-button="Approve Event"
    data-confirm-variant="success"
>
    @csrf
    @method('PATCH')
    <input type="hidden" name="approval_status" value="approved">
    <button class="btn btn-sm btn-outline-success">Approve</button>
</form>
<form
    method="POST"
    action="{{ route('donation-schedules.review',$schedule) }}"
    class="d-inline js-confirm-action"
    data-confirm-title="Reject event?"
    data-confirm-message="This will reject {{ $schedule->title }} and keep it hidden from the public portal until changes are made."
    data-confirm-button="Reject Event"
    data-confirm-variant="danger"
>
    @csrf
    @method('PATCH')
    <input type="hidden" name="approval_status" value="rejected">
    <button class="btn btn-sm btn-outline-danger">Reject</button>
</form>
