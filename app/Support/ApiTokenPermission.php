<?php

namespace App\Support;

class ApiTokenPermission
{
    public const CREATE = 'create';
    public const READ = 'read';
    public const UPDATE = 'update';
    public const DELETE = 'delete';
    public const DEVICE_LOCATION = 'device:location';
    public const DEVICE_BARCODE = 'device:barcode';
    public const DEVICE_PHOTO = 'device:photo';
    public const DEVICE_PERMISSIONS = 'device:permissions';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::CREATE,
            self::READ,
            self::UPDATE,
            self::DELETE,
            self::DEVICE_LOCATION,
            self::DEVICE_BARCODE,
            self::DEVICE_PHOTO,
            self::DEVICE_PERMISSIONS,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function default(): array
    {
        return [
            self::READ,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function deviceApi(): array
    {
        return [
            self::DEVICE_LOCATION,
            self::DEVICE_BARCODE,
            self::DEVICE_PHOTO,
            self::DEVICE_PERMISSIONS,
        ];
    }
}
