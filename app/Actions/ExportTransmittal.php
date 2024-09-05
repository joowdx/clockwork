<?php

namespace App\Actions;

use App\Helpers\NumberRangeCompressor;
use App\Models\Employee;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LSNepomuceno\LaravelA1PdfSign\Sign\ManageCert;
use LSNepomuceno\LaravelA1PdfSign\Sign\SignaturePdf;
use SensitiveParameter;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ExportTransmittal implements Responsable
{
    private Collection $employee;

    private Carbon $month;

    private ?User $user = null;

    private bool $signature = false;

    private ?Closure $password = null;

    private string $period = 'full';

    private array $dates = [];

    private string $format = 'csc';

    private string $size = 'folio';

    private ?array $states = [];

    private ?array $modes = [];

    private array $groups = [];

    private bool $current = false;

    private bool $strict = false;

    public function __construct(
        Collection|Employee|null $employee = null,
        Carbon|string|null $month = null,
        string $period = 'full',
    ) {
        if ($employee) {
            $this->employee($employee);
        }

        if ($month) {
            $this->month($month);
        }

        $this->period($period);
    }

    public function __invoke(
        Collection|Employee $employee,
        Carbon|string $month,
        string $period = 'full',
        array $dates = [],
        string $format = 'csc',
        string $size = 'folio',
        bool $current = false,
        bool $strict = false,
        ?User $user = null,
        bool $signature = false,
        #[SensitiveParameter]
        ?string $password = null,
    ): StreamedResponse {
        return $this->employee($employee)
            ->month($month)
            ->period($period)
            ->dates($dates)
            ->format($format)
            ->strict($strict)
            ->current($current)
            ->size($size)
            ->user($user)
            ->signature($signature)
            ->password($password)
            ->download();
    }

    public function password(#[SensitiveParameter] ?string $password): static
    {
        $this->password = is_string($password) ? fn (): string => $password : $password;

        return $this;
    }

    public function user(?User $user = null): static
    {
        $this->user = $user;

        return $this;
    }

    public function signature(bool $signature = true): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function current(bool $current = false): static
    {
        $this->current = $current;

        return $this;
    }

    public function strict(bool $strict = false): static
    {
        $this->strict = $strict;

        return $this;
    }

    public function employee(Collection $employee): static
    {
        $this->employee = $employee;

        return $this;
    }

    public function month(Carbon|string $month): static
    {
        $this->month = is_string($month) ? Carbon::parse($month) : $month;

        return $this;
    }

    public function period(string $period = 'full'): static
    {
        if (! in_array(explode('|', $period)[0], ['full', '1st', '2nd', 'overtime', 'regular', 'dates', 'range'])) {
            throw new InvalidArgumentException('Unknown period: '.$period);
        }

        $this->period = $period;

        return $this;
    }

    public function states(Collection|array|null $states): static
    {
        $this->states = collect($states)
            ->map(fn ($state) => $state instanceof UnitEnum ? $state->value : $state)
            ->toArray();

        return $this;
    }

    public function modes(Collection|array|null $modes): static
    {
        $this->modes = collect($modes)
            ->map(fn ($mode) => $mode instanceof UnitEnum ? $mode->value : $mode)
            ->toArray();

        return $this;
    }

    public function dates(array $dates): static
    {
        $this->dates = collect($dates)
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->toArray();

        return $this;
    }

    public function format(string $format = 'csc'): static
    {
        if (! in_array($format, ['csc', 'default'])) {
            throw new InvalidArgumentException('Unknown format: '.$format);
        }

        $this->format = $format;

        return $this;
    }

    public function size(string $size = 'folio'): static
    {
        if (! in_array(mb_strtolower($size), ['a4', 'letter', 'folio', 'legal'])) {
            throw new InvalidArgumentException('Unknown size: '.$size);
        }

        $this->size = mb_strtolower($size);

        return $this;
    }

    public function groups(array $groups = []): static
    {
        $this->groups = $groups;

        return $this;
    }

    public function download(): BinaryFileResponse|StreamedResponse
    {
        if (! $this->signature && ! is_null($this->password)) {
            throw new InvalidArgumentException('Signature is required when password is provided');
        }

        if ($this->format === 'default' && in_array($this->period, ['regular', 'overtime'])) {
            throw new InvalidArgumentException('Default format is not supported for regular and overtime period');
        }

        return $this->export();
    }

    protected function export(): StreamedResponse
    {
        $name = $this->filename().'.pdf';

        $headers = ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'"'];

        $downloadable = match ($this->password) {
            null => $this->pdf(),
            default => $this->signed(),
        };

        return response()->streamDownload(fn () => print ($downloadable), $name, $headers);
    }

    protected function pdf(bool $base64 = true): PdfBuilder|string
    {
        @[$period, $range] = explode('|', $this->period, 2);

        if ($period === 'range') {
            [$from, $to] = explode('-', $range, 2);
        } else {
            $from = match ($period) {
                '2nd' => 16,
                default => 1,
            };

            $to = match ($period) {
                '1st' => 15,
                default => $this->month->daysInMonth,
            };
        }

        $employees = match ($this->format) {
            'csc' => $this->employee->toQuery()->whereHas('timesheets', fn ($q) => $q->whereDate('month', $this->month.'-01')),
            default => $this->employee->toQuery(),
        };

        if ($this->groups) {
            $employees->whereHas('groups', fn ($q) => $q->whereIn('groups.name', $this->groups)->orWhereIn('groups.id', $this->groups));
        }

        $args = [
            'employees' => $employees->get(),
            'size' => $this->size,
            'user' => $this->user,
            'signature' => $this->signature,
            'signed' => (bool) $this->password,
            'strict' => $this->strict,
            'current' => $this->current,
            'states' => $this->states,
            'modes' => $this->modes,
            'month' => $this->month,
            'from' => $period !== 'dates' ? $from : null,
            'to' => $period !== 'dates' ? $to : null,
            'dates' => $period === 'dates' ? $this->dates : null,
            'period' => $period,
            'format' => $this->format,
        ];

        $export = Pdf::view('print.transmittal.csc-default', [...$args, 'signed' => (bool) $this->password])
            ->withBrowsershot(fn (Browsershot $browsershot) => $browsershot->noSandbox()->setOption('args', ['--disable-web-security']));

        match ($this->size) {
            'folio' => $export->paperSize(8.5, 13, 'in'),
            default => $export->format($this->size),
        };

        return $base64 ? base64_decode($export->base64()) : $export;
    }

    protected function signed(): string
    {
        $export = $this->pdf(false);

        $name = $this->filename().'.pdf';

        $export->save(sys_get_temp_dir()."/$name");

        $signature = $this->user->signature;

        $certificate = (new ManageCert)->setPreservePfx()->fromPfx(storage_path('app/'.$signature->certificate), ($this->password)());

        try {
            return (new SignaturePdf(sys_get_temp_dir()."/$name", $certificate))->signature();
        } finally {
            if (file_exists(sys_get_temp_dir()."/$name")) {
                unlink(sys_get_temp_dir()."/$name");
            }
        }
    }

    protected function filename(): string
    {
        $prefix = 'Transmittal '.$this->month->format('Y-m ').match ($this->period) {
            'full' => '',
            '1st' => '(First half)',
            '2nd' => '(Second half)',
            'overtime' => '(Overtime Work)',
            'regular' => '(Regular Days)',
            'dates' => (new NumberRangeCompressor)(collect($this->dates)->map(fn ($date) => Carbon::parse($date)->format('j'))->values()->toArray()),
            default => '('.str($this->period)->replace('custom|', '').')',
        };

        $name = $this->employee instanceof Collection ? substr($this->employee->pluck('last_name')->sort()->join(','), 0, 60) : $this->employee->name;

        return "$prefix ($name)";
    }

    public function toResponse($request): BinaryFileResponse|StreamedResponse
    {
        return $this->download();
    }
}
