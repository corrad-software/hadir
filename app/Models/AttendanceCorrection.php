<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    use HasFactory, Auditable;

    protected $connection = 'pgsql_attendance';

    protected $fillable = [
        'attendance_log_id',
        'user_id',
        'corrected_check_in_at',
        'corrected_check_out_at',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_note',
    ];

    protected function casts(): array
    {
        return [
            'corrected_check_in_at'  => 'datetime',
            'corrected_check_out_at' => 'datetime',
            'reviewed_at'            => 'datetime',
        ];
    }

    public function attendanceLog(): BelongsTo
    {
        return $this->belongsTo(AttendanceLog::class);
    }
}
