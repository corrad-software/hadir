<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'parent_id',
        'attendance_policy_id',
    ];

    protected function casts(): array
    {
        return [
            'attendance_policy_id' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Division::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'division_id');
    }
}
