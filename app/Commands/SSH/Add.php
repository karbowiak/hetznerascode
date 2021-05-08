<?php

namespace Hac\Commands\SSH;

use Hac\Helpers\Hetzner;
use LKDev\HetznerCloud\APIException;
use LKDev\HetznerCloud\HetznerAPIClient;
use LKDev\HetznerCloud\Models\SSHKeys\SSHKey;
use New3den\Console\ConsoleCommand;

/**
 * Class AddSSHKey
 * @package Hac\Commands
 * @property bool $addLocal
 */
class Add extends ConsoleCommand
{
    protected string $signature = 'ssh:add {--addLocal}';
    protected string $description = 'Add SSH Key to use when creating servers';
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
        $name = $this->ask('What is the name of the SSH Key ?');
        if ($this->addLocal) {
            $publicKey = file_get_contents($_SERVER['HOME'] . '/.ssh/id_rsa.pub');
        } else {
            $publicKey = $this->ask('What is the public key contents?');
        }
        $label = $this->ask('What are the labels for this? separate multiple labels by comma', '');
        $labels = !empty($label) ? explode(',', $label) : [];

        /** @var SSHKey $result */
        $result = $this->client->sshKeys()->create($name, $publicKey, $labels);

        $this->table(['name', 'fingerprint', 'key', 'labels'], [
            $result->name,
            $result->fingerprint,
            substr($result->public_key, 0, 25),
            implode(',', $result->labels)
        ]);
    }
}