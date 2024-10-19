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

    protected ?string $out = null;

    protected ?string $field = null;

    protected ?string $coordinates = null;

    protected int $page = 1;

    protected array $data = [];

    protected bool $certify = false;

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

    public function __invoke(
        User|Employee $user,
        string $path,
        ?string $out = null,
        ?string $field = null,
        ?string $coordinates = null,
        int $page = 1,
        array $data = [],
        bool $certify = false,
    ): void {
        $this->field($field)
            ->coordinates($coordinates)
            ->page($page)
            ->data($data)
            ->certify($certify)
            ->sign($user, $path, $out);
    }

    public function sign(User|Employee $user, string $path, ?string $out): void
    {
        if ($user->signature === null) {
            throw new RuntimeException('User signature is not yet configured');
        }

        $this->user = $user;

        $this->path = $path;

        $this->out = $out;

        try {
            $directory = storage_path('signing/'.str($this->id())->slug('_')->append('/'));

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($directory.'certificate.pfx', base64_decode($user->signature->certificateBase64));
            file_put_contents($directory.'signature.webp', base64_decode($user->signature->specimenBase64));
            file_put_contents($directory.'password', $user->signature->password);
            file_put_contents($directory.'pyhanko.yml', $this->yml());

            $timestamp = env('TIMESTAMP_URL') !== null;

            do {
                $process = Process::forever()
                    ->path($directory)
                    ->run($this->command($timestamp));

                if ($process->failed()) {
                    if ($timestamp && str($process->errorOutput())->contains('Timestamp')) {
                        $timestamp = false;

                        continue;
                    }

                    throw new RuntimeException($process->errorOutput());
                }

                break;
            } while (1);
        } finally {
            if ($directory && is_dir($directory)) {
                Process::run(['rm', '-rf', $directory]);
            }
        }
    }

    public function field(?string $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function coordinates(?string $coordinates): static
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    public function page(int $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function certify(bool $certify): static
    {
        $this->certify = $certify;

        return $this;
    }

    public function command(bool $timestamp = true): array
    {
        $command = [
            ...(is_string($this->pyhanko) ? [$this->pyhanko] : $this->pyhanko),
            '--verbose',
            'sign',
            'addsig',
            "--field={$this->page}/{$this->coordinates}/{$this->field}",
        ];

        if ($this->certify) {
            $command[] = '--certify';
        }

        if ($timestamp && env('TIMESTAMP_URL')) {
            $command[] = '--timestamp-url='.env('TIMESTAMP_URL');
        }

        if (isset($this->data['reason'])) {
            $command[] = "--reason={$this->data['reason']}";
        }

        return array_merge($command, [
            '--contact-info='.($this->data['contact'] ?? $this->user->email),
            '--location='.($this->data['location'] ?? 'Philippines'),
            'pkcs12',
            '--passfile=password',
            $this->path,
            $this->out ?? $this->path,
            'certificate.pfx',
        ]);
    }

    public function id(): string
    {
        return "{$this->user->id}-{$this->path}";
    }

    public function yml(): string
    {
        return <<<'YML'
        stamp-styles:
          default:
            type: text
            stamp-text: "Signed by %(signer)s\nTimestamp: %(ts)s"
            background: "signature.webp"
            background-opacity: 1
            border-width: 0
            inner-content-layout:
              y-align: bottom
        YML;
    }
}
