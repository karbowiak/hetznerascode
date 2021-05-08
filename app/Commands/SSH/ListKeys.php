<?php

namespace Hac\Commands\SSH;

use Hac\Helpers\Hetzner;
use LKDev\HetznerCloud\APIException;
use LKDev\HetznerCloud\HetznerAPIClient;
use LKDev\HetznerCloud\Models\SSHKeys\SSHKey;
use New3den\Console\ConsoleCommand;

class ListKeys extends ConsoleCommand
{
    protected string $signature = 'ssh:list';
    protected string $description = 'List SSH Keys';
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
        $keys = $this->client->sshKeys()->all();

        $this->table(['name', 'fingerprint', 'key', 'labels'], array_map(static function($key) {
            /** @var SSHKey $key */
            return [
                $key->name,
                $key->fingerprint,
                substr($key->public_key, 0, 25),
                implode(',', $key->labels)
            ];
        }, $keys));
    }
}