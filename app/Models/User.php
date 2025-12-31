<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'profile_picture',
        'department_id',
        'sub_department_id',
        'designation',
        'role',
        'weekend_days',
        'nid_number',
        'joining_date',
        'closing_date',
        'permanent_address',
        'present_address',
        'nationality',
        'fathers_name',
        'mothers_name',
        'graduated_institution',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'weekend_days' => 'array',
            'joining_date' => 'date',
            'closing_date' => 'date',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the department that the user belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the sub-department that the user belongs to.
     */
    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    /**
     * Get the sub-departments that this manager manages.
     * If manager has a sub_department, they manage only that sub_department.
     * If manager has no sub_department but has a department, they manage all sub_departments in that department.
     */
    public function getManagedSubDepartments()
    {
        if (!$this->isManager() && !$this->isAdmin()) {
            return collect([]);
        }

        // If manager has a specific sub_department, they manage only that one
        if ($this->sub_department_id) {
            return SubDepartment::where('id', $this->sub_department_id)->get();
        }

        // If manager has a department but no sub_department, they manage all sub_departments in that department
        if ($this->department_id) {
            return SubDepartment::where('department_id', $this->department_id)->get();
        }

        return collect([]);
    }

    /**
     * Get the IDs of sub-departments that this manager manages.
     */
    public function getManagedSubDepartmentIds(): array
    {
        return $this->getManagedSubDepartments()->pluck('id')->toArray();
    }

    /**
     * @deprecated This relationship is no longer used. Use getManagedSubDepartments() instead.
     */
    public function managedSubDepartments(): BelongsToMany
    {
        return $this->belongsToMany(SubDepartment::class, 'manager_sub_departments');
    }

    /**
     * Check if a given day is a weekend for this user
     */
    public function isWeekend(string $dayName): bool
    {
        $weekendDays = $this->weekend_days ?? ['saturday', 'sunday'];
        return in_array(strtolower($dayName), array_map('strtolower', $weekendDays));
    }

    /**
     * Scope to filter users by department
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter users by sub-department
     */
    public function scopeInSubDepartment($query, $subDepartmentId)
    {
        return $query->where('sub_department_id', $subDepartmentId);
    }
}
