<?php

namespace App\Actions;

use App\Helpers\NumberRangeCompressor;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use LSNepomuceno\LaravelA1PdfSign\Sign\ManageCert;
use LSNepomuceno\LaravelA1PdfSign\Sign\SignaturePdf;
use SensitiveParameter;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webklex\PDFMerger\Facades\PDFMergerFacade;
use Webklex\PDFMerger\PDFMerger;
use ZipArchive;

use function Safe\tmpfile;

class ExportTimesheet implements Responsable
{
    private Collection|Employee $employee;

    private Carbon $month;

    private ?User $user = null;

    private bool $signature = false;

    private ?Closure $password = null;

    private string $period = 'full';

    private array $dates = [];

    private string $format = 'csc';

    private string $size = 'folio';

    private int $transmittal = 0;

    private false|string|null $grouping = 'offices';

    private bool $individual = false;

    private array $misc = [];

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
        int $transmittal = 0,
        false|string|null $grouping = 'offices',
        bool $signature = false,
        #[SensitiveParameter]
        ?string $password = null,
        array $misc = [],
    ): StreamedResponse {
        return $this->employee($employee)
            ->month($month)
            ->period($period)
            ->dates($dates)
            ->format($format)
            ->size($size)
            ->transmittal($transmittal)
            ->grouping($grouping)
            ->signature($signature)
            ->password($password)
            ->misc($misc)
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

    public function signature(bool $signature = false): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function employee(Collection|Employee $employee): static
    {
        if ($employee instanceof Collection) {
            $employee->ensure(Employee::class);
        }

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

    public function dates(array $dates): static
    {
        $this->dates = collect($dates)
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->toArray();

        return $this;
    }

    public function format(string $format = 'csc'): static
    {
        if (! in_array($format, ['default', 'csc', 'preformatted'])) {
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

    public function individual(bool $individual = true): static
    {
        $this->individual = $individual;

        return $this;
    }

    public function transmittal(int $transmittal = 0): static
    {
        $this->transmittal = $transmittal;

        return $this;
    }

    public function grouping(false|string|null $grouping = 'offices'): static
    {
        $this->grouping = $grouping === '0' ? false : $grouping;

        return $this;
    }

    public function misc(array $misc): static
    {
        $this->misc = $misc;

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

        @[$period, $range] = explode('|', $this->period, 2);

        @[$from, $to] = explode('-', $range, 2);

        if ($this->format === 'default') {
            $timelogs = function ($query) use ($period, $from, $to) {
                $query->month($this->month->startOfMonth());

                match ($period) {
                    '1st' => $query->firstHalf(),
                    '2nd' => $query->secondHalf(),
                    'dates' => $query->customDates($this->dates),
                    'range' => $query->customRange($from, $to),
                    default => $query,
                };
            };

            $this->employee->load(['timelogs' => $timelogs, 'timelogs.scanner', 'scanners'])->sortBy('full_name');

            return match ($this->individual) {
                true => $this->exportAsZip(),
                default => $this->exportAsPdf(),
            };
        }

        if ($this->format === 'preformatted') {
            [$from, $to] = match ($period) {
                'dates' => null,
                'range' => [$from, $to],
                default => [
                    match ($period) {
                        '2nd' => 16,
                        default => 1,
                    },
                    match ($period) {
                        '1st' => 15,
                        default => $this->month->daysInMonth,
                    },
                ],
            };

            $this->employee->load([
                'currentDeployment.supervisor',
                'currentDeployment.office.head',
                'timelogs.scanner',
                'timelogs' => function ($query) use ($period, $from, $to) {
                    [$year, $month] = explode('-', $this->month);

                    $query->whereYear('time', $year)->whereMonth('time', $month);

                    match ($period) {
                        '1st' => $query->firstHalf(),
                        '2nd' => $query->secondHalf(),
                        'dates' => $query->customDates($this->dates),
                        'range' => $query->customRange($from, $to),
                        default => $query,
                    };

                    $query->reorder()->orderBy('time');
                },
            ]);

            return match ($this->individual) {
                true => $this->exportAsZip(),
                default => $this->exportAsPdf(),
            };
        }

        $uid = $this->employee instanceof Collection ? $this->employee->pluck('uid')->toArray() : [$this->employee->uid];

        $timesheets = Timesheet::query()
            ->whereHas('employee', fn ($query) => $query->whereIn('uid', $uid))
            ->whereDate('month', $this->month->startOfMonth())
            ->when($period === '1st', fn ($query) => $query->with('firstHalf'))
            ->when($period === '2nd', fn ($query) => $query->with('secondHalf'))
            ->when($period === 'regular', fn ($query) => $query->with('regularDays'))
            ->when($period === 'overtime', fn ($query) => $query->with('overtimeWork'))
            ->with(['employee:id,name,status'])
            ->orderBy(Employee::select('full_name')->whereColumn('employees.id', 'timesheets.employee_id')->limit(1))
            ->lazy();

        $timesheets = match ($period) {
            '1st' => $timesheets->map->setFirstHalf(),
            '2nd' => $timesheets->map->setSecondHalf(),
            'overtime' => $timesheets->map->setOvertimeWork(),
            'regular' => $timesheets->map->setRegularDays(),
            'dates' => $timesheets->map->setCustomDates($this->dates),
            'range' => $timesheets->map->setCustomRange($from, $to),
            default => $timesheets,
        };

        if ($this->transmittal > 0 && $this->grouping !== false) { //grouping only available to office
            $timesheets = $timesheets->groupBy(fn ($timesheet) => $timesheet->employee->offices->pluck('code')->toArray())->flatten();
        }

        return match ($this->individual) {
            true => $this->exportAsZip($timesheets),
            default => $this->exportAsPdf($timesheets),
        };
    }

    protected function exportAsZip(LazyCollection|Collection|null $exportable = null): BinaryFileResponse
    {
        $name = $this->filename().'.zip';

        $headers = ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="'.$name.'"'];

        $temp = stream_get_meta_data(tmpfile())['uri'];

        $zip = new ZipArchive;

        $zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->setCompressionIndex(-1, ZipArchive::CM_STORE);

        match ($exportable) {
            null => $this->employee->each(function ($employee) use ($zip) {
                $content = match ($this->password) {
                    null => $this->pdf([$employee]), default => $this->signed([$employee])
                };

                $zip->addFromString($employee->name.'.pdf', $content);
            }),

            default => $exportable->each(function ($timesheet) use ($zip) {
                $content = match ($this->password) {
                    null => $this->pdf([$timesheet]), default => $this->signed([$timesheet])
                };

                $zip->addFromString($timesheet->employee->name.'.pdf', $content);
            })
        };

        $zip->close();

        return response()->download($temp, $name, $headers)->deleteFileAfterSend();
    }

    protected function exportAsPdf(LazyCollection|Collection|null $exportable = null): StreamedResponse
    {
        $name = $this->filename().'.pdf';

        $headers = ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'"'];

        $downloadable = match ($this->password) {
            null => $this->pdf($exportable),
            default => $this->signed($exportable),
        };

        return response()->streamDownload(fn () => print ($downloadable), $name, $headers);
    }

    protected function pdf(?iterable $exportable, bool $base64 = true): PdfBuilder|PDFMerger|string
    {
        @[$period, $range] = explode('|', $this->period, 2);

        if ($period === 'range') {
            [$from, $to] = explode('-', $range, 2);
        } elseif ($period !== 'dates') {
            $from = match ($period) {
                '2nd' => 16,
                default => 1,
            };

            $to = match ($period) {
                '1st' => 15,
                default => $this->month->daysInMonth,
            };
        }

        if ($this->format === 'csc') {
            $args = [
                'timesheets' => $exportable,
                'size' => $this->size,
                'signature' => $this->signature,
            ];
        } elseif ($this->format === 'preformatted') {
            $args = [
                'employees' => $this->employee,
                'size' => $this->size,
                'signature' => $this->signature,
                'month' => $this->month,
                'from' => $period !== 'dates' ? $from : null,
                'to' => $period !== 'dates' ? $to : null,
                'dates' => $period === 'dates' ? $this->dates : null,
                'period' => $period,
            ];
        } else {
            $employees = match ($exportable) {
                null => match (get_class($this->employee)) {
                    Collection::class, EloquentCollection::class, LazyCollection::class => $this->employee,
                    default => EloquentCollection::make([$this->employee])
                },
                default => match (is_array($exportable)) {
                    true => EloquentCollection::make([$exportable]),
                    default => $exportable,
                },
            };

            $args = [
                'employees' => $employees,
                'size' => $this->size,
                'signature' => $this->signature,
                'month' => $this->month,
                'from' => $period !== 'dates' ? $from : null,
                'to' => $period !== 'dates' ? $to : null,
                'dates' => $period === 'dates' ? $this->dates : null,
                'period' => $period,
            ];
        }

        $view = match ($this->format) {
            'csc' => 'print.csc',
            'preformatted' => 'print.preformatted',
            default => 'print.default',
        };

        $export = Pdf::view($view, [...$args, 'misc' => $this->misc, 'user' => $this->user ?? Auth::user(), 'signed' => (bool) $this->password])
            ->withBrowsershot(fn (Browsershot $browsershot) => $browsershot->noSandbox()->setOption('args', ['--disable-web-security']));

        match ($this->size) {
            'folio' => $export->paperSize(8.5, 13, 'in'),
            default => $export->format($this->size),
        };

        if ($this->transmittal) {
            $transmittal = Pdf::view('print.transmittal.csc-default', [
                ...$args,
                'format' => $this->format,
                'copies' => $this->transmittal,
                'user' => $this->user ?? Auth::user(),
                'signed' => (bool) $this->password,
                'month' => $this->month,
                'from' => $args['from'] ?? $from ?? null,
                'to' => $args['to'] ?? $to ?? null,
                'dates' => $args['dates'] ?? $this->dates,
                'period' => $args['period'] ?? $period,
                'employees' => $this->format === 'csc'
                    ? EloquentCollection::make(collect($exportable)->pluck('employee'))
                    : $args['employees'],
                'misc' => $this->misc,
            ])
                ->withBrowsershot(fn (Browsershot $browsershot) => $browsershot->noSandbox()->setOption('args', ['--disable-web-security']));

            match ($this->size) {
                'folio' => $transmittal->paperSize(8.5, 13, 'in'),
                default => $transmittal->format($this->size),
            };

            $merger = PDFMergerFacade::init();

            if (! is_dir(storage_path('tmp'))) {
                mkdir(storage_path('tmp'));
            }

            $merger->addString(base64_decode($transmittal->base64()), 'all')->addString(base64_decode($export->base64()), 'all');

            $merger->merge();

            return $base64 ? $merger->output() : $merger;
        }

        return $base64 ? base64_decode($export->base64()) : $export;
    }

    protected function signed(?iterable $timesheets): string
    {
        $export = $this->pdf($timesheets, false);

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
        $prefix = 'Timesheets '.$this->month->format('Y-m ').match ($this->period) {
            'full' => '',
            '1st' => '(First half)',
            '2nd' => '(Second half)',
            'overtime' => '(Overtime Work)',
            'regular' => '(Regular Days)',
            'dates' => (new NumberRangeCompressor)(collect($this->dates)->map(fn ($date) => Carbon::parse($date)->format('j'))->values()->toArray()),
            default => '('.str($this->period)->replace('range|', '').')',
        };

        $name = $this->employee instanceof Collection ? substr($this->employee->pluck('last_name')->sort()->join(','), 0, 60) : $this->employee->name;

        return "$prefix ($name)";
    }

    public function toResponse($request): BinaryFileResponse|StreamedResponse
    {
        return $this->download();
    }
}
