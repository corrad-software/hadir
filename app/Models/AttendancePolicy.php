<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Database\Factories\AttendancePolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePolicy extends Model
{
    /** @use HasFactory<AttendancePolicyFactory> */
    use HasFactory, Auditable;

    protected $connection = 'pgsql_attendance';

    protected $fillable = [
        'name',
        'work_days',
        'start_time',
        'end_time',
        'grace_period_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'work_days'             => 'array',
            'grace_period_minutes'  => 'integer',
            'is_active'             => 'boolean',
        ];
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class, 'policy_id');
    }
}
