<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class SignPdfAction
{
    const FOLIO_TIMESHEET_EMPLOYEE_COORDINATES = '256,216,356,266';

    const FOLIO_TIMESHEET_SUPERVISOR_COORDINATES = '256,163,356,213';

    const FOLIO_TIMESHEET_HEAD_COORDINATES = '256,98,356,148';

    protected User|Employee|null $user = null;

    protected ?string $python;

    protected array|string|null $pyhanko;

    protected string $path = '';

    protected string $out = '';

    protected string $field = 'sig-1';

    protected string $coordinates = '';

    protected string $pages = '1';

    protected array $data = [];

    public function __construct()
    {
        $this->python = trim(`which python3` ?? `which python`);

        if ($this->python === null) {
            throw new RuntimeException('Python interpreter required');
        }

        $this->pyhanko = trim(`pyhanko --version`) ? 'pyhanko' : (
            trim(`{$this->python} -m pyhanko --version`) !== null
                ? ["{$this->python}", '-m', 'pyhanko']
                : null
        );

        if ($this->pyhanko === null) {
            throw new RuntimeException('PyHanko module is not found or installed');
        }
    }

    public function invoke(
        User|Employee $user,
        string $path,
        string $out,
        string $field,
        string $coordinates,
        string $pages,
        array $data = [],
    ): void {
        $this->field($field)
            ->coordinates($coordinates)
            ->pages($pages)
            ->data($data)
            ->sign($user, $path, $out);
    }

    public function sign(User|Employee $user, string $path, string $out): void
    {
        if ($user->signature === null) {
            throw new RuntimeException('User signature is not yet configured');
        }

        $this->user = $user;

        $this->path = $path;

        $this->out = $out;

        try {
            $certificate = tempnam(sys_get_temp_dir(), 'signing_') . 'pfx';

            file_put_contents($certificate, base64_decode($user->signature->certificateBase64()));

            $process = Process::forever()
                ->path(storage_path('signing/'.$this->user->id))
                ->input($user->certificate->password)
                ->run($this->command($certificate));
        } finally {
            if ($certificate && file_exists($certificate)) {
                unlink($certificate);
            }
        }
    }

    public function field(string $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function coordinates(string $coordinates): static
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    public function pages(string $pages): static
    {
        $this->pages = $pages;

        return $this;
    }

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function command(string $certificate): array
    {
        $command = [
            ...(is_string($this->pyhanko) ? [$this->pyhanko] : $this->pyhanko),
            'sign',
            'addsig',
            "--field={$this->pages}/{$this->coordinates}/{$this->field}",
        ];

        if (isset($this->data['reason'])) {
            $command[] = "--reason={$this->data['reason']}";
        }

        return array_merge($command, [
            '--contact-info='.($this->data['contact'] ?? $this->user->email),
            '--location='.($this->data['location'] ?? 'Philippines'),
            'pkcs12',
            $this->path,
            $this->out,
            $certificate,
        ]);
    }

    public function id(): string
    {
        return "{$this->user->id}-{$this->path}";
    }

    public function yml(): string
    {
        return <<<YML

        YML;
    }
}
