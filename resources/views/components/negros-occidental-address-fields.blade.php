@props([
    'address' => null,
    'required' => true,
    'cityClass' => 'col-md-4',
    'barangayClass' => 'col-md-4',
    'provinceClass' => 'col-md-4',
])

@php
    $addressValue = old('address', $address);
    $selectedAddress = \App\Support\NegrosOccidentalAddress::split($addressValue);
    $cities = \App\Support\NegrosOccidentalAddress::cities();
    $barangaysByCity = \App\Support\NegrosOccidentalAddress::barangaysByCity();
    $fieldId = 'negros-address-'.uniqid();
@endphp

<div
    id="{{ $fieldId }}"
    class="row g-3 js-negros-address"
    data-selected-city="{{ $selectedAddress['city'] }}"
    data-selected-barangay="{{ $selectedAddress['barangay'] }}"
>
    <script type="application/json" class="js-negros-address-data">@json($barangaysByCity)</script>
    <input type="hidden" name="address" value="{{ $addressValue }}" @required($required)>

    <div class="{{ $provinceClass }}">
        <label class="form-label">Province</label>
        <input class="form-control" value="{{ \App\Support\NegrosOccidentalAddress::PROVINCE }}" disabled>
    </div>

    <div class="{{ $cityClass }}">
        <label class="form-label">City / Municipality</label>
        <select class="form-select js-negros-city" @required($required)>
            <option value="">Select city / municipality</option>
            @foreach($cities as $city)
                <option value="{{ $city }}" @selected($selectedAddress['city'] === $city)>{{ \App\Support\NegrosOccidentalAddress::cityLabel($city) }}</option>
            @endforeach
        </select>
    </div>

    <div class="{{ $barangayClass }}">
        <label class="form-label">Barangay</label>
        <select class="form-select js-negros-barangay" @required($required) disabled>
            <option value="">Select barangay</option>
        </select>
    </div>

    @error('address')
        <div class="col-12">
            <div class="text-danger small">{{ $message }}</div>
        </div>
    @enderror
</div>

@once
    @push('scripts')
        <script>
        document.querySelectorAll('.js-negros-address').forEach((wrapper) => {
            const barangaysByCity = JSON.parse(wrapper.querySelector('.js-negros-address-data')?.textContent || '{}');
            const citySelect = wrapper.querySelector('.js-negros-city');
            const barangaySelect = wrapper.querySelector('.js-negros-barangay');
            const hiddenAddress = wrapper.querySelector('input[name="address"]');
            const selectedCity = wrapper.dataset.selectedCity || '';
            const selectedBarangay = wrapper.dataset.selectedBarangay || '';
            const province = @json(\App\Support\NegrosOccidentalAddress::PROVINCE);

            function fillBarangays(city, selected = '') {
                barangaySelect.innerHTML = '<option value="">Select barangay</option>';
                (barangaysByCity[city] || []).forEach((barangay) => {
                    const option = document.createElement('option');
                    option.value = barangay;
                    option.textContent = barangay;
                    option.selected = barangay === selected;
                    barangaySelect.appendChild(option);
                });
                barangaySelect.disabled = !city;
            }

            function syncAddress() {
                const city = citySelect.value;
                const barangay = barangaySelect.value;
                hiddenAddress.value = city && barangay ? `${barangay}, ${city}, ${province}` : '';
            }

            citySelect.addEventListener('change', () => {
                fillBarangays(citySelect.value);
                syncAddress();
            });

            barangaySelect.addEventListener('change', syncAddress);

            if (selectedCity) {
                citySelect.value = selectedCity;
                fillBarangays(selectedCity, selectedBarangay);
                syncAddress();
            }
        });
        </script>
    @endpush
@endonce
