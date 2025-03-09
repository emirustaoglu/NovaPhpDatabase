<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | Varsayılan veritabanı bağlantısı.
    | Birden fazla bağlantı arasından hangisinin kullanılacağını belirtir.
    |
    */
    'default' => 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Veritabanı bağlantı ayarları. Birden fazla bağlantı tanımlanabilir.
    | Her bağlantı için ayrı ayarlar yapılabilir.
    |
    | Desteklenen sürücüler: "mysql", "pgsql", "sqlite", "sqlsrv"
    |
    */
    'connections' => [
        'mysql' => [
            // MySQL/MariaDB sürücüsü
            'driver' => 'mysql',

            // Veritabanı sunucu adresi
            'host' => 'localhost',

            // Veritabanı port numarası
            'port' => 3306,

            // Veritabanı adı
            'database' => 'dijitalofisim',

            // Veritabanı kullanıcı adı
            'username' => 'miyoas',

            // Veritabanı şifresi
            'password' => 'miyoas',

            // Karakter seti
            'charset' => 'utf8mb4',

            // Karakter karşılaştırma ayarı
            'collation' => 'utf8mb4_unicode_ci',

            // Bakım modu - true ise bağlantılar reddedilir
            'maintanceMode' => false
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => 'database.sqlite',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'myapp',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'schema' => 'public',
        ],
    ],
];
