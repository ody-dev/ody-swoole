<?php
declare(strict_types=1);
namespace Ody\Swoole;

//use Ody\Core\Http\Request;
use Ody\Core\Kernel;
use Ody\Swoole\Coroutine\ContextManager;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

/**
 * @psalm-api
 */
class Http
{
    private Server $server;

    public function __construct(string $host, int $port)
    {
        $this->server = new Server(
            $host,
            $port,
            !is_null(config('server.ssl.ssl_cert_file')) && !is_null(config('server.ssl.ssl_key_file')) ? config('server.mode') | SWOOLE_SSL : config('server.mode') ,
            config('server.sockType')
        );
    }

    /**
     * Starts the server
     *
     * @param bool $daemonize
     * @return void
     */
    public function start(bool $daemonize = false): void
    {
        if ($daemonize === true){
            $this->server->set([
                'daemonize' => 1
            ]);
        }

        $this->server->start();
    }

    /**
     * @param Kernel $app
     * @return Http
     */
    public function createServer(Kernel $app): Http
    {
        \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
        $this->server->set([
            ...config('server.additional')
        ]);

        $this->server->on('request', function(Request $request, Response $response) use ($app) {
            Coroutine::create(function() use ($request, $response, $app) {
                // Set global variables in the ContextManager
                $this->setContext($request);

                // Create the app and handle the request
                (new RequestCallback($app))->handle($request, $response);
            });
        });
        $this->server->on('workerStart', [$this, 'onWorkerStart']);

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
}
