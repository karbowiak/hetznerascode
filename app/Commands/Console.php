<?php

namespace Hac\Commands;

use Hac\Interfaces\CommandsInterface;
use Psy\Configuration;
use Psy\Shell;

class Console extends CommandsInterface
{
    protected string $signature = 'console';

    protected string $description = 'Console';

    public function handle(): void
    {
        try {
            // Check for php_manual
            $manualPath = '~/.local/share/psysh';
            $manualName = 'php_manual.sqlite';
            if (!is_dir($manualPath) && !mkdir($manualPath, 0777, true) && !is_dir($manualPath)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $manualPath));
            }
            if (!file_exists($manualPath . '/' . $manualName)) {
                $this->out('Downloading PHP Manual, one moment...');
                copy('http://psysh.org/manual/en/php_manual.sqlite', $manualPath . '/' . $manualName);
            }
        } catch (\Exception $e) {
            $this->out("<bg=red>{$e->getMessage()}</>");
        }
        $shell = new Shell(new Configuration([]));
        $shell->setScopeVariables([
            'container' => $this->container,
        ]);
        $shell->run();
    }
}
