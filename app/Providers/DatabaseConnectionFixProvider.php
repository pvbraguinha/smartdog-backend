<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;

class DatabaseConnectionFixProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->resolving(DatabaseManager::class, function ($db) {
            $db->extend('pgsql', function ($config, $name) {
                $config['host'] = 'dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com';
                $config['port'] = '5432';
                $config['database'] = 'smartdog_db_fnp8';
                $config['username'] = 'smartdog_db_fnp8_user';
                $config['password'] = '0SMTQjMgkWVSii6sUumnTXNfBp8qweKd';
                
                return $db->factory->make($config, $name);
            });
        });
    }
}