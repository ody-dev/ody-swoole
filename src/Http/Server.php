<?php
declare(strict_types=1);

namespace Ody\Swoole\Http;

use Ody\Core\Kernel;
use Ody\Swoole\Coroutine\ContextManager;
use Ody\Swoole\RequestCallback;
use Ody\Swoole\ServerState;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as SwooleServer;

/**
 * @psalm-api
 */
class Server
{
    /**
     * @var SwooleServer
     */
    private SwooleServer $server;

    /**
     * @var Kernel
     */
    private Kernel $kernel;


    public function __construct() {}

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
     * @param Kernel $kernel
     * @param bool $daemonize
     * @return Server
     */
    public function createServer(Kernel $kernel, bool $daemonize = false): Server
    {
        $this->kernel = $kernel;
        $this->server = new SwooleServer(
            config('server.host'),
            config('server.port'),
            $this->getSslConfig(),
            config('server.sock_type')
        );

        // \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
        $this->server->set([...config('server.additional'), 'daemonize' => (int) $daemonize]);
        $this->server->on('request', [$this, 'onRequest']);
        $this->server->on('workerStart', [$this, 'onWorkerStart']);

        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function onRequest(Request $request, Response $response): void
    {
        Coroutine::create(function() use ($request, $response) {
            // Set global variables in the ContextManager
            $this->setContext($request);

            // Create the app and handle the request
            (new RequestCallback($this->kernel))
                ->handle($request, $response);
        });
    }

    /**
     * @param SwooleServer $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStart(SwooleServer $server, int $workerId): void
    {
        if ($workerId == config('server.additional.worker_num') - 1){
            $this->saveWorkerIds($server);
        }
    }

    /**
     * @param SwooleServer $server
     * @return void
     */
    protected function saveWorkerIds(SwooleServer $server): void
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

    /**
     * @param Request $request
     * @return void
     */
    private function setContext(\Swoole\Http\Request $request): void
    {
        ContextManager::set('_GET', (array)$request->get);
        ContextManager::set('_GET', (array)$request->get);
        ContextManager::set('_POST', (array)$request->post);
        ContextManager::set('_FILES', (array)$request->files);
        ContextManager::set('_COOKIE', (array)$request->cookie);
        ContextManager::set('_SERVER', (array)$request->server);
        ContextManager::set('request', \Ody\Core\Http\Request::getInstance());
    }

    /**
     * @return int
     */
    private function getSslConfig(): int
    {
        if (
            !is_null(config('server.ssl.ssl_cert_file')) &&
            !is_null(config('server.ssl.ssl_key_file'))
        ) {
            return config('server.mode', SWOOLE_SSL) | SWOOLE_SSL;
        }

        return config('server.mode');
    }
}
