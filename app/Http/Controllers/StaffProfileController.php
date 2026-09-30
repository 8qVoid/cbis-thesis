<?php

namespace App\Http\Controllers;

use App\Support\PhilippinePhone;
use App\Traits\LogsAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffProfileController extends Controller
{
    use LogsAudit;

    public function edit(Request $request): View
    {
        return view('staff-profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($request->filled('phone')) {
            $request->merge(['phone' => PhilippinePhone::normalizeMobileInput((string) $request->input('phone')) ?? trim((string) $request->input('phone'))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', "regex:/^[\\pL\\s.'-]+$/u"],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^\+639\d{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        DB::transaction(function () use ($user, $data, $request): void {
            $before = $user->only(['name', 'email', 'phone']);
            $user->fill($data);
            $changes = [];
            foreach (['name', 'email', 'phone'] as $field) {
                if ($user->isDirty($field)) {
                    $changes[$field] = ['before' => $before[$field], 'after' => $user->$field];
                }
            }

            if ($changes !== []) {
                $user->save();
                $this->logAudit('staff_profile.updated', $user, ['changes' => $changes], $request);
            }
        });

        return redirect()->route('staff-profile.edit')->with('success', 'Profile updated.');
    }
}
