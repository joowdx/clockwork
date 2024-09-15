<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Connection
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the storage connection that will
    | be used to store backup's data (not the actual backup).
    |
    */

    'connection' => env('BACKUP_CONNECTION', 'sqlite'),
];
