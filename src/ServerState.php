<?php

namespace Ody\Swoole;


class ServerState
{
    protected readonly string $path;

    public function __construct(){
        $this->path = storagePath($this->serverType . 'State.json');
    }

    /**
     * @param string $key
     * @param int|array|null $id
     * @return void
     */
    protected function setId(string $key, int|array|null $id): void
    {
        file_put_contents($this->path, json_encode(
            [
                $this->serverType => [
                    'pIds' => array_merge($this->getInformation(), [$key => $id])
                ]
            ],
            JSON_PRETTY_PRINT
        ));
    }

    /**
     * @return array
     */
    public function getInformation(): array
    {
        $data = is_readable($this->path)
            ? json_decode(file_get_contents($this->path), true)
            : [];

        return [
            'masterProcessId' => $data[$this->serverType]['pIds']['masterProcessId'] ?? null ,
            'managerProcessId' => $data[$this->serverType]['pIds']['managerProcessId'] ?? null ,
            'watcherProcessId' => $data[$this->serverType]['pIds']['watcherProcessId'] ?? null ,
            'workerProcessIds' => $data[$this->serverType]['pIds']['workerProcessIds'] ?? [] ,
        ];
    }

    /**
     * @psalm-api
     */
    public function setManagerProcessId(?int $id): void
    {
        $this->setId('managerProcessId', $id);
    }

    /**
     * @psalm-api
     */

    public function setMasterProcessId(?int $id): void
    {
        $this->setId('masterProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setWatcherProcessId(?int $id): void
    {
        $this->setId('watcherProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setWorkerProcessIds(array $ids): void
    {
        $this->setId('workerProcessIds', $ids);
    }

    /**
     * @psalm-api
     */
    public function getManagerProcessId(): int|null
    {
        return $this->getInformation()['managerProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getMasterProcessId(): int|null
    {
        return $this->getInformation()['masterProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getWatcherProcessId(): int|null
    {
        return $this->getInformation()['watcherProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getWorkerProcessIds(): array
    {
        return $this->getInformation()['workerProcessIds'];
    }

    public function clearProcessIds(): void
    {
        $this->setWorkerProcessIds([]);
        $this->setMasterProcessId(null);
        $this->setManagerProcessId(null);
        $this->setWatcherProcessId(null);
    }

    /**
     * @param array $processIds
     * @return void
     */
    public function reloadProcesses(array $processIds): void
    {
        foreach ($processIds as $processId) {
            posix_kill($processId , SIGUSR1);
        }

    }

    /**
     * @param array $processIds
     * @return void
     */
    public function killProcesses(array $processIds): void
    {
        foreach ($processIds as $processId) {
            if (!is_null($processId) && posix_kill($processId, SIG_DFL)){
                posix_kill($processId, SIGTERM);
            }
        }
    }
}