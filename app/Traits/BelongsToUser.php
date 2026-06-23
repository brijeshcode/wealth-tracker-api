<?php

namespace App\Traits;

use App\Models\Scopes\BelongsToUserScope;

trait BelongsToUser
{
    protected static function bootBelongsToUser(): void
    {
        static::addGlobalScope(new BelongsToUserScope());
    }
}
