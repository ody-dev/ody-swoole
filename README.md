# Ody Swoole
Run Ody on Swoole HTTP server

## Usage
Start a Swoole server

```php
/**
 * Returns an $app instance, in theory this could be anything 
 * as long as it handles psr7 requests/responses. You could for 
 * example plug a Slim framework instance in here.
 */
$kernel = Kernel::init();

(new Http())->createServer(
    $kernel,
    $this->host,
    $this->port
)->start(),
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

