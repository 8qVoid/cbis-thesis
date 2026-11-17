<?php

namespace App\Http\Controllers;

use App\Models\DonationRecord;
use Illuminate\View\View;

class DonorDonationHistoryController extends Controller
{
    public function __invoke(): View
    {
        $donorId = auth()->user()->donorProfile?->id;
        $donations = DonationRecord::query()
            ->with(['facility', 'bloodlettingRecord'])
            ->where('donor_id', $donorId ?? 0)
            ->orderByDesc('donated_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('account.donation-history', compact('donations'));
    }
}
