<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'module',
        'field_name',
        'is_hidden',
        'is_readonly',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'is_readonly' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
