<?php

return [
    'mode' => SWOOLE_BASE,
    'host' => '127.0.0.1',
    'port' => 9501 ,
    'sockType' => SWOOLE_SOCK_TCP,
    'additional' => [
        'worker_num' => 2,
        /*
         * log level
         * SWOOLE_LOG_DEBUG (default)
         * SWOOLE_LOG_TRACE
         * SWOOLE_LOG_INFO
         * SWOOLE_LOG_NOTICE
         * SWOOLE_LOG_WARNING
         * SWOOLE_LOG_ERROR
         */
        'log_level' => SWOOLE_LOG_DEBUG ,
        'log_file' => '',
        'open_http_protocol' => true,
        'enable_coroutine' => true // Required for context to work
    ],

    'ssl' => [
        'ssl_cert_file' => null ,
        'ssl_key_file' => null ,
    ] ,

    /*
     * The following services are created for better performance 
     * in the program, only one object is created from them and 
     * they can be used throughout the program
     */
    'services' => [] ,

    /*
     * Files and folders that must be changed in real time
     */
    'watcher' => [
        'App',
        'config',
        'database',
        'composer.lock',
        '.env',
    ] 
];
