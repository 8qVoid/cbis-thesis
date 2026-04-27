<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffUserRequest;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class StaffUserController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $query = User::query()->with('facility');

        if (! $user->isCentralAdmin()) {
            $query->where('facility_id', $user->facility_id);
        }

        $users = $query->latest()->paginate(20);

        return view('staff-users.index', compact('users'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $facilities = $user->isCentralAdmin()
            ? Facility::query()->where('is_active', true)->orderBy('name')->get()
            : Facility::query()->where('is_active', true)->whereKey($user->facility_id)->get();
        $roleNames = [
            'Facilitator',
            'Medical Staff / Nurse',
        ];
        $roles = Role::query()
            ->whereIn('name', $roleNames)
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, $roleNames, true))
            ->values();

        return view('staff-users.create', compact('facilities', 'roles'));
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $currentUser = auth()->user();

        if (! $currentUser->isCentralAdmin()) {
            $data['facility_id'] = $currentUser->facility_id;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'facility_id' => $data['facility_id'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()->route('staff-users.index')->with('success', 'Staff account created by admin.');
    }
}
