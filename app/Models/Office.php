<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Office extends Model
{
    use HasFactory, Auditable;

    protected $connection = 'pgsql_attendance';

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_meters',
        'policy_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude'      => 'float',
            'longitude'     => 'float',
            'radius_meters' => 'integer',
            'policy_id'     => 'integer',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id');
    }
}
