<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    'branches' => [
        'nursery_vgc' => [
            'name' => 'Chrisland Nursery School VGC',
            'address' => "0-1A / 3D, Road 9,\nVictoria Garden City (VGC), Ajah\nLagos, NG",
            'phones' => '08172013114, 08023421214',
        ],
        'primary_vgc' => [
            'name' => 'Chrisland Primary School VGC',
            'address' => "K-6-A Road 3,\nVictoria Garden City (VGC), Ajah,\nLagos, NG",
            'phones' => '08172013145, 08023369900, 014540221',
        ],
        'high_school_vgc' => [
            'name' => 'Chrisland High School VGC',
            'address' => "K-6-B Road 3,\nVictoria Garden City (VGC), Ajah\nLagos, NG",
            'phones' => '08130101818, 08034106700, 09020876340',
        ],
        'school_abuja' => [
            'name' => 'Chrisland School Abuja',
            'address' => "Plot 84, Cadastral Zone C10,\nWumba District, Off Apo Mechanic Village,\nAbuja, NG",
            'phones' => '08033348747, 08030839541, 08075908888',
        ],
        'high_school_abuja' => [
            'name' => 'Chrisland High School Abuja',
            'address' => "Plot 84, Cadastral Zone C10,\nWumba District, Off Apo Mechanic Village,\nAbuja, NG",
            'phones' => '08075918888, 08036448479, 08033915928',
        ],
        'school_festac' => [
            'name' => 'Chrisland School Festac Area',
            'address' => "Plot 277A, Oladipo Coker Road, Palm Vale Estate\nDurbar Road, Amuwo Odofin, Amuwo Odofin, GRA\nLagos, NG",
            'phones' => '08087579810, 08094236404, 07038587224',
        ],
        'high_school_festac' => [
            'name' => 'Chrisland High School Festac Area',
            'address' => "Plot 277A, Oladipo Coker Road, Palm Vale Estate\nOff Durbar Road, Amuwo Odofin\nFestac, Lagos, NG",
            'phones' => '08172013123, 08101192841',
        ],
        'school_ladipo' => [
            'name' => 'Chrisland School Ladipo Oluwole',
            'address' => "2A-D, Ladipo Oluwole Off Adeniyi Jones,\nIkeja, Lagos, NG",
            'phones' => '08147677310, 08034065896',
        ],
        'school_lekki' => [
            'name' => 'Chrisland School Lekki',
            'address' => "No 4, Olubunmi Owa Street\nOff Admiralty Way, Lekki Phase 1\nLagos, NG",
            'phones' => '07088088408, 08057469697',
        ],
        'high_school_lekki' => [
            'name' => 'Chrisland High School Lekki',
            'address' => "Plot 105A, Hakeem Dickson Way,\nLekki Phase 1, Lekki\nLagos, NG",
            'phones' => '07013872662, 08160824973, 08099503659',
        ],
        'school_opebi' => [
            'name' => 'Chrisland School Opebi',
            'address' => "26 Opebi Road,\nIkeja, Lagos. NG",
            'phones' => '08037193587, 08023050901, 02014542479',
        ],
        'high_school_ikeja' => [
            'name' => 'Chrisland High School Ikeja',
            'address' => "28 Opebi Road,\nIkeja, Lagos. NG",
            'phones' => '08134366988, 08035807364',
        ],
        'college_idimu' => [
            'name' => 'Chrisland College',
            'address' => "72, Old Ejigbo Road,\nIdimu, Lagos. NG",
            'phones' => '08023096656',
        ],
        'pre_degree_lekki' => [
            'name' => 'Chrisland Pre-Degree College',
            'address' => "4, Olubunmi Owa Street,\nOff Admiralty Way, Lekki Phase 1\nLagos, NG",
            'phones' => '08172013127, 08023869783',
        ],
        'college_katampe' => [
            'name' => 'Chrisland College Katampe Abuja',
            'address' => "5, Samuel Oladele Crescent by Gishiri Bus/Stop,\nOpposite Nicon Junction, Katampe District,\nFCT Abuja",
            'phones' => '09023824909, 08038398628',
        ],
        'school_katampe' => [
            'name' => 'Chrisland School Katampe Abuja',
            'address' => "5, Samuel Oladele Crescent by Gishiri Bus/Stop,\nOpposite Nicon Junction, Katampe District,\nFCT Abuja",
            'phones' => '08023199882, 08036485770, 07031355323',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Super Admin Production Credentials
    |--------------------------------------------------------------------------
    |
    | When deploying to production, the initial database seed uses these keys
    | to securely configure the master administrative account away from defaults.
    |
    */

    'admin_username' => env('ADMIN_USERNAME', 'admin_root'),
    'admin_email' => env('ADMIN_EMAIL', 'admin@chrisland.org'),
    'admin_password' => env('ADMIN_PASSWORD', 'password'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Africa/Lagos'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
