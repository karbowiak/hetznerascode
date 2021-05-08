<?php

namespace Hac\Commands;

use Hac\Helpers\Hetzner;
use LKDev\HetznerCloud\HetznerAPIClient;
use New3den\Console\ConsoleCommand;

class CreateServer extends ConsoleCommand
{
    protected string $signature = 'create:server';
    protected string $description = 'Create a server';
    protected HetznerAPIClient $client;

    public function __construct(
        protected Hetzner $hetzner,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->client = $hetzner->getClient();
    }

    /**
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function handle(): void
    {
        // Get data from Hetzner
        $serverTypes = $this->client->serverTypes()->all();
        $images = $this->client->images()->all();
        $locations = $this->client->locations()->all();
        $sshKeys = $this->client->sshKeys()->all();

        // Ask questions
        $name = strtolower(html_entity_decode($this->ask('Server name')));
        $location = $this->askWithOptions('Server location', array_map(static function($location) {
            return $location->name;
        }, $locations));
        $type = $this->askWithOptions('Select server type', array_map(static function($server) {
            return $server->name;
        }, $serverTypes));
        $image = $this->askWithOptions('Select image to deploy', array_map(static function($image) {
            return $image->name;
        }, $images));
        $sshKey = $this->askWithOptions('Select SSH Key to use', array_map(static function($sshKey) {
            return $sshKey->name;
        }, $sshKeys));

        // Output the server information
        $this->table(['name', 'type', 'image', 'location', 'sshKey'], [$name, $type, $image, $location, $sshKey]);

        // Create server
        $server = $this->client->servers()->createInLocation(
            $name,
            $this->client->serverTypes()->get($type),
            $this->client->images()->get($image),
            $this->client->locations()->get($location),
            [
                $sshKey
            ],
            true,
            '',
            [],
            false
        )->getResponse();

        $this->table(
            ['name', 'image', 'ipv4', 'ipv6', 'memory', 'cores', 'disk', 'hourlyPrice', 'monthlyPrice'],
            [
                $server['server']->name,
                $server['server']->image->name,
                $server['server']->publicNet->ipv4->ip,
                $server['server']->publicNet->ipv6->ip,
                $server['server']->serverType->memory,
                $server['server']->serverType->cores,
                $server['server']->serverType->disk,
                $server['server']->serverType->prices[0]->price_hourly->gross,
                $server['server']->serverType->prices[0]->price_monthly->gross
            ]
        );
    }
}
