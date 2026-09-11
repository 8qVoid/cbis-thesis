@props(['status'])
@php
    $help = [
        'submitted' => 'Received. Blood Bank Staff will review the request and documents.',
        'under_review' => 'Blood Bank Staff are reviewing this request. A decision has not been saved yet.',
        'approved' => 'Stock has been reserved. Blood Bank Staff must record the release to complete the request.',
        'rejected' => 'This request was declined. Check the review notes for the reason.',
        'fulfilled' => 'All requested units have recorded blood releases.',
        'cancelled' => 'This request is closed and will not proceed.',
    ];
    $tone = in_array($status, ['approved', 'fulfilled']) ? 'success' : (in_array($status, ['rejected', 'cancelled']) ? 'danger' : 'warning');
@endphp
<span class="cbis-inline-status cbis-tone-{{ $tone }}">{{ str($status)->headline() }}</span>
@if(isset($help[$status]))<small class="d-block text-muted mt-2">{{ $help[$status] }}</small>@endif
