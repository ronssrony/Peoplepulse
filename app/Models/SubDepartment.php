<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubDepartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the department that owns the sub-department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the users in this sub-department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the managers assigned to this sub-department.
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'manager_sub_departments', 'sub_department_id', 'user_id')
            ->wherePivot('user_id', function($query) {
                $query->where('role', 'manager');
            });
    }

    /**
     * Scope to get only active sub-departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
