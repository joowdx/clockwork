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

    public function command(string $args = ""): string
    {
        if (empty($this->python)) {
            throw new \RuntimeException("Python interpreter not found. Please install Python.");
        }

        if ($this->scanner->port) {
            $args = "-P {$this->scanner->port} $args";
        }

        if ($this->scanner->password) {
            $args = "-K {$this->scanner->password} $args";
        }

        if (! $this->ping) {
            $args = "--ping 0 $args";
        }

        return trim("$this->python $this->script {$this->scanner->ip_address} $args");
    }
}
