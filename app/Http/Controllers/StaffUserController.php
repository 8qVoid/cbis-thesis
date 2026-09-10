<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffUserRequest;
use App\Http\Requests\UpdateStaffUserRequest;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class StaffUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'in:all,staff,public'],
            'role' => ['nullable', 'in:Quality Assurance Officer,Event Facilitator,Blood Bank Staff,donor,patient,both'],
            'status' => ['nullable', 'in:active,inactive'],
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $user = auth()->user();
        $query = User::query()->with(['facility', 'roles']);

        if (! $user->isCentralAdmin()) {
            $query->where('facility_id', $user->facility_id);
        }

        $staffRoles = ['Quality Assurance Officer', 'Event Facilitator', 'Blood Bank Staff'];
        $category = $filters['category'] ?? 'all';
        if ($category === 'staff') {
            $query->whereHas('roles', fn ($roles) => $roles->whereIn('name', $staffRoles));
        } elseif ($category === 'public') {
            $query->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['Donor', 'Patient']))
                ->whereDoesntHave('roles', fn ($roles) => $roles->whereIn('name', $staffRoles));
        }
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(email) LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhere('phone', 'like', '%'.$search.'%'));
        }
        if ($role = $filters['role'] ?? null) {
            if (in_array($role, ['donor', 'patient', 'both'], true)) {
                if ($role !== 'patient') $query->whereHas('roles', fn ($q) => $q->where('name', 'Donor'));
                if ($role !== 'donor') $query->whereHas('roles', fn ($q) => $q->where('name', 'Patient'));
                if ($role === 'donor') $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Patient'));
                if ($role === 'patient') $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Donor'));
            } else {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            }
        }
        if (! empty($filters['status'])) $query->where('is_active', $filters['status'] === 'active');
        if (! empty($filters['facility_id'])) $query->where('facility_id', $filters['facility_id']);
        $users = $query->latest()->paginate(20)->withQueryString();
        $facilities = Facility::query()->when(! $user->isCentralAdmin(), fn ($q) => $q->whereKey($user->facility_id))->orderBy('name')->get();

        return view('staff-users.index', compact('users', 'facilities', 'category'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $facilities = $user->isCentralAdmin()
            ? Facility::query()->where('is_active', true)->orderBy('name')->get()
            : Facility::query()->where('is_active', true)->whereKey($user->facility_id)->get();
        $roleNames = [
            'Event Facilitator',
            'Blood Bank Staff',
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

    public function edit(Request $request, User $staffUser): View
    {
        $this->authorizeStaffAccess($request->user(), $staffUser);

        return view('staff-users.edit', compact('staffUser'));
    }

    public function update(UpdateStaffUserRequest $request, User $staffUser): RedirectResponse
    {
        $data = $request->validated();

        $staffUser->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        return redirect()->route('staff-users.index')->with('success', 'Account contact details updated.');
    }

    public function updateStatus(Request $request, User $staffUser): RedirectResponse
    {
        $this->authorizeStaffAccess($request->user(), $staffUser);

        if ($staffUser->is($request->user())) {
            return back()->withErrors(['staff' => 'You cannot deactivate your own account.']);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $staffUser->forceFill([
            'is_active' => (bool) $data['is_active'],
        ])->save();

        $message = $staffUser->is_active
            ? 'Account reactivated.'
            : 'Account deactivated. The user can no longer log in.';

        return redirect()->route('staff-users.index')->with('success', $message);
    }

    private function authorizeStaffAccess(User $currentUser, User $staffUser): void
    {
        if ($currentUser->isCentralAdmin() && $currentUser->can('manage users')) {
            return;
        }

        if (
            ! $currentUser->can('manage users')
            || $currentUser->facility_id === null
            || $staffUser->facility_id !== $currentUser->facility_id
        ) {
            abort(403);
        }
    }
}
