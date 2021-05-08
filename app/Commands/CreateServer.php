<?php

namespace Hac\Commands;

use Hac\Helpers\Hetzner;
use New3den\Console\ConsoleCommand;

class CreateServer extends ConsoleCommand
{
    protected string $signature = 'create:server';

    protected string $description = 'Create a server';

    public function __construct(
        protected Hetzner $hetzner,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    public function handle(): void
    {
        $hetzner = $this->hetzner->getClient();
        // Get data from Hetzner
        $serverTypes = $hetzner->serverTypes()->all();
        $images = $hetzner->images()->all();

        // Ask questions
        $serverName = strtolower(html_entity_decode($this->ask('Server name')));
        $serverType = $this->askWithOptions('Select server type', array_map(function($server) {
            return $server->name;
        }, $serverTypes));
        $image = $this->askWithOptions('Select image to deploy', array_map(function($image) {
            return $image->name;
        }, $images));

        // Output the server information
        $this->table(['name', 'type', 'image'], [$serverName, $serverType, $image]);

        // Create server
        $server = $hetzner->servers()->createInLocation(
            $serverName,
            $hetzner->serverTypes()->get($serverType),
            $hetzner->images()->get($image),
            $hetzner->locations()->get('fsn1'),
            [],
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
