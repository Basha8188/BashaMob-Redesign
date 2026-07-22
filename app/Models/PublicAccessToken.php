<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicAccessToken extends Model
{
    protected $guarded = [];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'last_used_at' => 'datetime', 'metadata' => 'array'];
    }

    public function tokenable()
    {
        return $this->morphTo();
    }
}
