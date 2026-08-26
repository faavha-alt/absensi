<?php

use App\Http\Middleware\LogAndFilterAdmsRequest;
use Syofyanzuhad\FilamentZktecoAdms\Models\AttendanceLog;
use Syofyanzuhad\FilamentZktecoAdms\Models\Device;
use Syofyanzuhad\FilamentZktecoAdms\Models\DeviceCommand;
use Syofyanzuhad\FilamentZktecoAdms\Models\ZktecoUser;

// config for Syofyanzuhad/FilamentZktecoAdms

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all package database tables. All tables will be created with
    | this prefix (e.g., zkteco_devices, zkteco_attendance_logs, etc.)
    |
    */
    'table_prefix' => env('ZKTECO_TABLE_PREFIX', 'zkteco_'),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the ADMS endpoint routes. The prefix will be used for all
    | ADMS routes (e.g., /iclock/cdata, /iclock/getrequest, etc.)
    |
    */
    'routes' => [
        'prefix' => env('ZKTECO_ROUTE_PREFIX', 'iclock'),
        'middleware' => ['api', LogAndFilterAdmsRequest::class],
        'domain' => env('ZKTECO_ROUTE_DOMAIN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Settings
    |--------------------------------------------------------------------------
    |
    | Configure device behavior settings.
    |
    */
    'device' => [
        // Automatically register new devices when they connect
        'auto_register' => env('ZKTECO_AUTO_REGISTER_DEVICES', false),

        // Minutes after which a device is considered offline
        'offline_threshold_minutes' => env('ZKTECO_OFFLINE_THRESHOLD', 10),

        // Default transmission interval in seconds
        'default_trans_interval' => 1,

        // Maximum records per transmission
        'default_trans_times' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Settings
    |--------------------------------------------------------------------------
    |
    | Configure the default response values sent to devices.
    |
    */
    'response' => [
        // Default stamp value for new devices
        'stamp' => 9999999999,

        // Retry delay on error (seconds)
        'error_delay' => 60,

        // Normal polling delay (seconds)
        'delay' => 30,

        // 1 = realtime mode enabled
        'realtime' => 1,

        // 0 = no encryption
        'encrypt' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Classes
    |--------------------------------------------------------------------------
    |
    | You can override the default model classes if you need to extend them.
    |
    */
    'models' => [
        'device' => Device::class,
        'attendance_log' => AttendanceLog::class,
        'user' => ZktecoUser::class,
        'device_command' => DeviceCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Toggle dispatching of events for various actions.
    |
    */
    'events' => [
        'dispatch_attendance_received' => true,
        'dispatch_device_connected' => true,
        'dispatch_user_synced' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Integration
    |--------------------------------------------------------------------------
    |
    | Configure the Filament admin panel integration.
    |
    */
    'security' => [
        // Daftar IP publik device yang boleh push ke /iclock/* (pisahkan koma). Kosong = tidak dibatasi IP.
        'allowed_ips' => env('ZKTECO_ALLOWED_IPS', ''),
    ],

    'filament' => [
        'navigation_group' => 'ZKTeco ADMS',
        'navigation_icon' => 'heroicon-o-finger-print',
        'navigation_sort' => 50,
    ],
];
