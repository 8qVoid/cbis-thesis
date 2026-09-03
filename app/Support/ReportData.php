<?php

namespace App\Support;

use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\BloodReservation;
use App\Models\DonationRecord;
use App\Models\User;

class ReportData
{
    public const TYPES = ['inventory' => 'Inventory', 'donations' => 'Donations', 'releases' => 'Blood Releases', 'reservations' => 'Reservations'];

    public static function sections(array $types, string $detail, ?string $from, ?string $to, User $user): array
    {
        $sections = [];
        foreach (array_unique($types) as $type) {
            [$model, $date, $columns, $headings, $quantity] = match ($type) {
                'inventory' => [BloodInventory::class, 'created_at', ['id', 'blood_type', 'component', 'units_available', 'expiration_date', 'status'], ['Record', 'Blood type', 'Component', 'Units', 'Expiry', 'Status'], 'units_available'],
                'donations' => [DonationRecord::class, 'donated_at', ['donation_no', 'blood_type', 'volume_ml', 'donated_at', 'status'], ['Donation', 'Blood type', 'Volume (ml)', 'Donated at', 'Status'], 'volume_ml'],
                'releases' => [BloodRelease::class, 'released_at', ['id', 'blood_inventory_id', 'units_released', 'released_at'], ['Release', 'Inventory record', 'Units', 'Released at'], 'units_released'],
                'reservations' => [BloodReservation::class, 'created_at', ['reference', 'blood_type', 'component', 'units_requested', 'needed_on', 'status'], ['Reference', 'Blood type', 'Component', 'Units requested', 'Needed on', 'Status'], 'units_requested'],
            };
            $records = FacilityScope::apply($model::query(), $user)
                ->when($from, fn ($q) => $q->whereDate($date, '>=', $from))
                ->when($to, fn ($q) => $q->whereDate($date, '<=', $to))
                ->orderBy($date)->orderBy('id')->get();
            $sections[] = [
                'title' => self::TYPES[$type],
                'headings' => $headings,
                'rows' => $detail === 'summary' ? [] : $records->map(fn ($record) => array_map(function ($column) use ($record) {
                    $value = $record->{$column};

                    return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : (string) ($value ?? '');
                }, $columns))->all(),
                'summary' => $detail === 'details' ? null : ['Records' => $records->count(), $quantity === 'volume_ml' ? 'Total volume (ml)' : 'Total units' => $records->sum($quantity)],
            ];
        }

        return $sections;
    }
}
