<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsoUser extends Model
{
    protected $connection = 'sso';

    protected $table = 'User';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'createdAt';

    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'id', 'email', 'name', 'passwordHash', 'role', 'avatarUrl',
    ];
}
