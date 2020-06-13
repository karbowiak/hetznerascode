<?php

namespace Hac\Helpers;

use Hac\Bootstrap;
use LKDev\HetznerCloud\HetznerAPIClient;

class Hetzner
{
    public Bootstrap $container;
    public HetznerAPIClient $hetzner;

    public function __construct(Bootstrap $container)
    {
        $this->container = $container;
        $this->hetzner = new HetznerAPIClient(getenv('HETZNERTOKEN'));
    }
}
