<?php

namespace Hac\Commands;

use Hac\Bootstrap;
use Hac\Helpers\Hetzner;
use LKDev\HetznerCloud\APIException;
use Hac\Interfaces\CommandsInterface;
use Psy\Configuration;
use Psy\Shell;
use LKDev\HetznerCloud\HetznerAPIClient;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CreateServer extends CommandsInterface
{
    protected string $signature = 'create:server';

    protected string $description = 'Create a server';

    protected HetznerAPIClient $hetzner;

    public function __construct(
        Bootstrap $container,
        string $name = null
    ) {
        parent::__construct($container, $name);
        $this->hetzner = $container->get('hetzner')->hetzner;
    }

    public function handle(): void
    {
        // Get data from Hetzner
        $serverTypes = $this->hetzner->serverTypes()->all();
        $images = $this->hetzner->images()->all();

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
        $server = $this->hetzner->servers()->createInLocation(
            $serverName,
            $this->hetzner->serverTypes()->get($serverType),
            $this->hetzner->images()->get($image),
            $this->hetzner->locations()->get('fsn1'),
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
