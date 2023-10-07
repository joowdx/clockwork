<?php

namespace App\Traits;

use App\Models\Scanner;

trait RunsZkScript
{
    protected string $python;

    protected string $script;

    protected string $ping;

    public function __construct(
        protected Scanner $scanner
    ) {
        $this->python = trim(`which python3` ?? `which python`);

        $this->script = base_path('python/'.@self::SCRIPT);

        $this->ping = is_string(`which ping`);
    }

    public function setScanner(Scanner $scanner): self
    {
        $this->scanner = $scanner;

        return $this;
    }

    public function getScanner(): Scanner
    {
        return $this->scanner;
    }

    public function command(null|string ...$args): array
    {
        if (empty($this->python)) {
            throw new \RuntimeException("Python interpreter not found. Please install Python.");
        }

        $command = [$this->python, $this->script, $this->scanner->ip_address];

        if ($this->scanner->port) {
            $args[] = "-P";
            $args[] = $this->scanner->port;
        }

        if ($this->scanner->password) {
            $args[] = "-K";
            $args[] = $this->scanner->password;
        }

        if (! $this->ping) {
            $args[] = "--no-ping";
        }

        return array_merge($command, array_diff($args, ['']));
    }
}
