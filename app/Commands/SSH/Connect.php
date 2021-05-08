<?php

namespace Hac\Commands\SSH;

use Hac\Helpers\Hetzner;
use LKDev\HetznerCloud\APIException;
use LKDev\HetznerCloud\HetznerAPIClient;
use LKDev\HetznerCloud\Models\Servers\Server;
use LKDev\HetznerCloud\Models\Servers\Servers;
use LKDev\HetznerCloud\Models\SSHKeys\SSHKey;
use New3den\Console\ConsoleCommand;

/**
 * Class Connect
 * @package Hac\Commands\SSH
 * @property string $server
 * @property string $username
 */
class Connect extends ConsoleCommand
{
    protected string $signature = 'ssh:connect { server? : Connect to a specific server } { --username=root }';
    protected string $description = 'Connect to a server via SSH';
    protected HetznerAPIClient $client;

    public function __construct(
        protected Hetzner $hetzner,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->client = $this->hetzner->getClient();
    }

    /**
     * @throws APIException
     */
    public function handle(): void
    {
        $servers = $this->client->servers()->all();
        if ($this->server === null) {
            $server = $this->askWithOptions('Select server you want to connect to', array_map(function ($server) {
                /** @var Server $server */
                if ($server->status === 'running') {
                    return $server->name;
                }

                return null;
            }, $servers));
        }

        $serverInfo = $this->client->servers()->getByName($this->server ?? $server);
        $ip = $serverInfo->publicNet->ipv4->ip;

        passthru("ssh {$this->username}@{$ip}");
    }
}