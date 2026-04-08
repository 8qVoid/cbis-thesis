<?php

namespace App\Exports;

use App\Models\BloodInventory;
use App\Models\User;
use App\Support\FacilityScope;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BloodInventoryExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
        private readonly User $user
    ) {
    }

    public function collection(): Collection
    {
        return FacilityScope::apply(BloodInventory::query(), $this->user)
            ->when($this->from, fn ($query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->whereDate('created_at', '<=', $this->to))
            ->get(['blood_type', 'units_available', 'expiration_date', 'status', 'created_at']);
    }

    public function headings(): array
    {
        return ['Blood Type', 'Units Available', 'Expiration Date', 'Status', 'Created At'];
    }
}
