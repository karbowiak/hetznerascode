<?php

namespace Hac\Helpers;

use LKDev\HetznerCloud\HetznerAPIClient;

class Hetzner
{
    public HetznerAPIClient $hetzner;

    public function __construct()
    {
        $this->hetzner = new HetznerAPIClient($_ENV['HETZNERTOKEN']);
    }

    public function getClient(): HetznerAPIClient
    {
        return $this->hetzner;
    }
}
