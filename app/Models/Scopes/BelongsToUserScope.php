<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BelongsToUserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // No-op outside request context (jobs, scheduled commands).
        // Those callers are responsible for their own scoping.
        if (Auth::check()) {
            $builder->where($model->getTable().'.user_id', Auth::id());
        }
    }
}
