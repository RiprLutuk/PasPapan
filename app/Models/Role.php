<?php

namespace App\Models;

use App\Support\RbacRegistry;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_system',
        'is_super_admin',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function grantsFullAdminAccess(): bool
    {
        $permissions = array_values(array_filter(
            $this->permissions ?? [],
            fn ($permission) => is_string($permission) && $permission !== '',
        ));

        if ($permissions === []) {
            return false;
        }

        return array_diff(RbacRegistry::permissionKeys(), $permissions) === [];
    }
}
