<?php
declare(strict_types=1);
namespace Ody\Swoole;

use Ody\Core\App;
use Ody\Core\Console\Style;
use Ody\Core\Http\Request;
use Ody\Swoole\Coroutine\ContextManager;
use Swoole\Coroutine;
use Swoole\Http\Server;

class Http
{
    private Server $server;

    public function __construct() {}

    /**
     * Starts the server
     *
     * @return void
     */
    public function start($daemonize = false): void
    {
        if ($daemonize === true){
            $this->server->set([
                'daemonize' => 1
            ]);
        }

        $this->server->start();
    }

    /**
     * @param App $app
     * @param string $host
     * @param int $port
     * @return Http
     */
    public function createServer(App $app, string $host, int $port): static
    {
        \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
        $server = new Server(
            $host,
            $port,
            !is_null(config('server.ssl.ssl_cert_file')) && !is_null(config('server.ssl.ssl_key_file')) ? config('server.mode') | SWOOLE_SSL : config('server.mode') ,
            config('server.sockType')
        );

        $server->set([
            ...config('server.additional'),
            ...['enable_coroutine' => true,]
        ]);

        $server->on('request', function($request, $response) use ($app) {
            Coroutine::create(function() use ($request, $response, $app) {
                // Set global variables in the ContextManager
                $this->setContext($request);

                // Create the app and handle the request
                (new RequestCallback($app))->handle($request, $response);
            });
        });
        $server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server = $server;

        return $this;
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

    private function setContext($request): void
    {
        ContextManager::set('_GET', (array)$request->get);
        ContextManager::set('_POST', (array)$request->post);
        ContextManager::set('_FILES', (array)$request->files);
        ContextManager::set('_COOKIE', (array)$request->cookie);
        ContextManager::set('_SERVER', (array)$request->server);
        ContextManager::set('request', Request::getInstance());
    }
}
