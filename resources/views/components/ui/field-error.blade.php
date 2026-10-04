@props(['field', 'id'])

@error($field)
    <div id="{{ $id }}" class="cbis-field-error invalid-feedback d-block" role="alert">{{ $message }}</div>
@enderror
