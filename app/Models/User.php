<?php

namespace App\Models;

use App\Notifications\AccountResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'birth_date',
        'sex',
        'email',
        'phone',
        'address',
        'facility_id',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function isCentralAdmin(): bool
    {
        return $this->hasRole(['Quality Assurance Officer', 'Super Administrator']) && $this->facility_id === null;
    }

    public function isQao(): bool { return $this->hasRole(['Quality Assurance Officer', 'Super Administrator']); }
    public function isBloodBankStaff(): bool { return $this->hasRole(['Blood Bank Staff', 'Medical Staff / Nurse']); }
    public function isEventFacilitator(): bool { return $this->hasRole(['Event Facilitator', 'Facilitator']); }
    public function hasDonorAccess(): bool { return $this->hasRole('Donor'); }
    public function hasPatientAccess(): bool { return $this->hasRole('Patient'); }

    public function donorProfile(): HasOne { return $this->hasOne(Donor::class); }
    public function patientProfile(): HasOne { return $this->hasOne(PatientProfile::class); }
    public function bloodReservations(): HasMany { return $this->hasMany(BloodReservation::class, 'patient_user_id'); }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AccountResetPasswordNotification($token, 'staff'));
    }
}
