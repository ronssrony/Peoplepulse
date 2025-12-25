<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'gross_minutes',
        'break_minutes',
        'net_minutes',
        'is_late',
        'late_minutes',
        'early_exit_minutes',
        'status',
        'clock_in_ip',
        'clock_out_ip',
        'clock_in_user_agent',
        'clock_out_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'is_late' => 'boolean',
            'gross_minutes' => 'integer',
            'break_minutes' => 'integer',
            'net_minutes' => 'integer',
            'late_minutes' => 'integer',
            'early_exit_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AttendanceAuditLog::class);
    }

    /**
     * Check if user has clocked in today
     */
    public function hasClockedIn(): bool
    {
        return $this->clock_in !== null;
    }

    /**
     * Check if user has clocked out today
     */
    public function hasClockedOut(): bool
    {
        return $this->clock_out !== null;
    }

    /**
     * Get formatted gross hours
     */
    public function getFormattedGrossHoursAttribute(): ?string
    {
        if ($this->gross_minutes === null) {
            return null;
        }

        $hours = floor($this->gross_minutes / 60);
        $minutes = $this->gross_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * Get formatted net hours
     */
    public function getFormattedNetHoursAttribute(): ?string
    {
        if ($this->net_minutes === null) {
            return null;
        }

        $hours = floor($this->net_minutes / 60);
        $minutes = $this->net_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get late records
     */
    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    /**
     * Scope for records with department filter via user relationship
     */
    public function scopeInDepartment($query, int $departmentId)
    {
        return $query->whereHas('user', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    /**
     * Scope for records with sub-department filter via user relationship
     */
    public function scopeInSubDepartment($query, int $subDepartmentId)
    {
        return $query->whereHas('user', function ($q) use ($subDepartmentId) {
            $q->where('sub_department_id', $subDepartmentId);
        });
    }
}
