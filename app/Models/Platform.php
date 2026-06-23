<?php

namespace App\Models;

use App\Traits\Authorable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Platform extends Model
{
    use Authorable, HasFactory, SoftDeletes;

    protected $fillable = ['name', 'display_name', 'type', 'supported_asset_types', 'logo_url'];

    protected function casts(): array
    {
        return ['supported_asset_types' => 'array'];
    }
}
