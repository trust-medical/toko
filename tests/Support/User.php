<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Tests\Support;

use Illuminate\Database\Eloquent\Model;

final class User extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
