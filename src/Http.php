<?php

namespace Ody\Swoole;

use Ody\Core\App;
use Ody\Core\Console\Style;
use Ody\Core\Env;
use Ody\Core\Facades\Facade;
use Ody\Core\Server\ContextManager;
use Ody\Core\Server\Dependencies;
use Swoole\Http\Server;

class Http
{
    private Server $server;

    private string $host;

    private int $port;

    public function __construct(
        private Style        $io,
    ) {
        $this->host = config('server.host');
        $this->port = config('server.port');
    }

    /**
     * Starts the server
     *
     * @return void
     */
    public function start(): void
    {
        $this->server->start();
    }

    /**
     * @return Http
     */
    public function createServer(): static
    {
        Dependencies::check($this->io);
        $this->createSwooleServer();

        return $this;
    }

    /**
     * Creates a Swoole HTTP server instance
     *
     * @return void
     */
    public function createSwooleServer(): void
    {
        $this->server = new Server(
            $this->host,
            $this->port,
            !is_null(config('server.ssl.ssl_cert_file')) && !is_null(config('server.ssl.ssl_key_file')) ? config('server.mode') | SWOOLE_SSL : config('server.mode') ,
            config('server.sockType')
        );
        \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
        $this->server->set([
            'enable_coroutine' => true,
        ]);

        $this->server->on('request', function($request, $response) {
            \Swoole\Coroutine::create(function() use ($request, $response) {
                // Set global variables in the ContextManager
                ContextManager::set('_GET', (array)$request->get);
                ContextManager::set('_POST', (array)$request->post);
                ContextManager::set('_FILES', (array)$request->files);
                ContextManager::set('_COOKIE', (array)$request->cookie);
                ContextManager::set('_SERVER', (array)$request->server);

                (new RequestCallback($this->initApp()))->handle($request, $response);
            });

        });
//        $this->server->on('request', ServerRequestFactory::createRequestCallback($this->initApp()));
        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->set(array_merge(config('server.additional') , ['enable_coroutine' => false]));
    }

    /**
     * Initialises the application
     *
     * @return App $app
     */
    public static function initApp(): App
    {
        Env::load(base_path());
        $app = \Ody\Core\DI\Bridge::create();
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware((bool) $_ENV['APP_DEBUG'], (bool) $_ENV['APP_DEBUG'], (bool) $_ENV['APP_DEBUG']);
        Facade::setFacadeApplication($app);

        /**
         * Register routes
         */
        require base_path('App/routes.php');

        /**
         * Register DB
         */
        if (class_exists('Ody\DB\Eloquent')) {
            \Ody\DB\Eloquent::boot(config('database.environments')[$_ENV['APP_ENV']]);
        }

        return $app;
    }

    public function onWorkerStart(Server $server, int $workerId): void
    {
        if ($workerId == config('server.additional.worker_num') - 1){
            $this->saveWorkerIds($server);
        }
    }

    protected function saveWorkerIds(Server $server): void
    {
        $workerIds = [];
        for ($i = 0; $i < config('server.additional.worker_num'); $i++){
            $workerIds[$i] = $server->getWorkerPid($i);
        }

        $serveState = ServerState::getInstance();
        $serveState->setMasterProcessId($server->getMasterPid());
        $serveState->setManagerProcessId($server->getManagerPid());
        $serveState->setWorkerProcessIds($workerIds);
    }
}
