<?php

namespace App\Support;

class RbacRegistry
{
    public static function sections(): array
    {
        return config('rbac.sections', []);
    }

    public static function modules(): array
    {
        return config('rbac.modules', []);
    }

    public static function module(string $key): ?array
    {
        return static::modules()[$key] ?? null;
    }

    public static function groupedModules(): array
    {
        $grouped = [];

        foreach (static::modules() as $key => $module) {
            $section = $module['section'] ?? 'system';

            $grouped[$section]['meta'] = static::sections()[$section] ?? [
                'label' => ucfirst(str_replace('_', ' ', $section)),
                'description' => null,
            ];
            $grouped[$section]['modules'][$key] = $module;
        }

        return $grouped;
    }

    public static function permissionKeys(): array
    {
        static $permissions;

        if ($permissions !== null) {
            return $permissions;
        }

        $permissions = [];

        foreach (static::modules() as $module) {
            foreach (($module['actions'] ?? []) as $action) {
                $permissions[] = $action['permission'];
            }
        }

        return array_values(array_unique($permissions));
    }

    public static function modulePermissionKeys(string $moduleKey): array
    {
        $module = static::module($moduleKey);

        if ($module === null) {
            return [];
        }

        return array_values(array_map(
            fn (array $action) => $action['permission'],
            $module['actions'] ?? [],
        ));
    }

    public static function resolveModuleActions(array $moduleActions): array
    {
        if (isset($moduleActions['*']) && in_array('*', (array) $moduleActions['*'], true)) {
            return static::permissionKeys();
        }

        $resolved = [];

        foreach ($moduleActions as $moduleKey => $actions) {
            $module = static::module($moduleKey);

            if ($module === null) {
                continue;
            }

            $availableActions = $module['actions'] ?? [];

            foreach ((array) $actions as $actionName) {
                if ($actionName === '*') {
                    $resolved = [
                        ...$resolved,
                        ...static::modulePermissionKeys($moduleKey),
                    ];

                    continue;
                }

                if (isset($availableActions[$actionName]['permission'])) {
                    $resolved[] = $availableActions[$actionName]['permission'];
                }
            }
        }

        return array_values(array_unique($resolved));
    }

    public static function presets(): array
    {
        static $presets;

        if ($presets !== null) {
            return $presets;
        }

        $presets = [];

        foreach (config('rbac.presets', []) as $slug => $preset) {
            $presets[$slug] = [
                'slug' => $slug,
                'name' => $preset['name'],
                'description' => $preset['description'] ?? null,
                'permissions' => static::resolveModuleActions($preset['permissions'] ?? []),
                'is_system' => (bool) ($preset['is_system'] ?? false),
                'is_super_admin' => (bool) ($preset['is_super_admin'] ?? false),
            ];
        }

        return $presets;
    }

    public static function adminAccessPermissions(): array
    {
        return static::permissionKeys();
    }
}
