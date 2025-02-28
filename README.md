# ody/swoole
Swoole support classes for ODY framework

## TODO/roadmap

- [x] Swoole HTTP server
- [ ] Websockets
- [ ] Connection pools (MySQL, Redis,...)
- [ ] Cache
- [ ] Documenation

### Websockets
WIP

```php
\Ody\Swoole\Websockets\Server::init('0.0.0.0', 9502)  // Host & port are nullable, will default to app.websockets config
    ->createServer()
    ->start();
```

### Hot reloading
```php
$serverState = ServerState::getInstance();
(new Process(function (Process $process) {
    $serverState->setWatcherProcessId($process->pid);
    (new Watcher())->start();
}))->start();
```

The `Watcher` class's constructor accepts a paths parameter, this is an array of
folder locations that need to be watched for changes. If no paths are specified the 
paths in `config/server.php` will be used. 

This class uses a helper function `base_path()` to determine the base path of your project.

```php
define('PROJECT_PATH' , realpath('./')); // Add this global constant to one of your bootstrap files.
function base_path(string $path = null): string
{
    return realpath(PROJECT_PATH) . "/$path";
}
```

### Context manager
When using coroutines it is possible to assign values to the coroutine context.
These values can be called from anywhere in the app.

```php
// Saves the GET query string to the coroutine context
ContextManager::set('_GET', (array)$request->get);

// Get the `_GET` parameters
ContextManager::get('_GET');

// Remove a parameter from the coroutine context
ContextManager::unset('_GET');
```

### Cache
Planned feature

### Server.php config file
```php
<?php

return [
    'mode' => SWOOLE_BASE,
    'host' => '127.0.0.1',
    'port' => 9501 ,
    'sockType' => SWOOLE_SOCK_TCP,
    'additional' => [
        'worker_num' => env('APP_WORKER_COUNT' , swoole_cpu_num() * 2) ,
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
        'log_file' => storagePath('logs/ody.log') ,
        'open_http_protocol' => true,
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
```

### Websockets.php config file
```php
<?php

return [
    'host' => env('WEBSOCKET_HOST', '127.0.0.1'),
    'port' => env('WEBSOCKET_PORT', 9502),
    'sock_type' => SWOOLE_SOCK_TCP,
    /**
     * Overwrite websocket events with your own callable methods (WIP)
     */
    'callbacks' => [
        Event::ON_HAND_SHAKE => [\Ody\Swoole\Websockets\Server::class, 'onHandShake'],
        Event::ON_MESSAGE => [\Ody\Swoole\Websockets\Server::class, 'onMessage'],
        Event::ON_CLOSE => [\Ody\Swoole\Websockets\Server::class, 'onClose'],
        Event::ON_REQUEST => [\Ody\Swoole\Websockets\Server::class, 'onRequest'],
    ],
    "additional" => [
        "worker_num" => env('APP_WEBSOCKET_WORKER_NUM', swoole_cpu_num() * 2),
    ]
];
```
