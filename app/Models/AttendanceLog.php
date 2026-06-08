<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Database\Factories\AttendanceLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    /** @use HasFactory<AttendanceLogFactory> */
    use HasFactory, Auditable;

    protected $connection = 'pgsql_attendance';

    protected $fillable = [
        'user_id',
        'work_date',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_within_radius',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_within_radius',
        'status',
        'notes',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_within_radius' => 'boolean',
            'check_out_at' => 'datetime',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_within_radius' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
