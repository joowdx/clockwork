<?php

namespace App\Actions;

use App\Helpers\NumberRangeCompressor;
use App\Models\Group;
use App\Models\Office;
use App\Models\Scanner;
use App\Models\Signature;
use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LSNepomuceno\LaravelA1PdfSign\Sign\ManageCert;
use LSNepomuceno\LaravelA1PdfSign\Sign\SignaturePdf;
use SensitiveParameter;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ExportAttendance implements Responsable
{
    private Collection|Model $office;

    private ?array $dates = [];

    private ?string $from = null;

    private ?string $to = null;

    private Collection|array|null $scanners = [];

    private ?array $states = [];

    private ?array $modes = [];

    private ?array $status = [];

    private ?array $substatus = [];

    private ?Signature $signature = null;

    private ?Closure $password = null;

    private bool $strict = false;

    private bool $transmittal = false;

    private string $size = 'folio';

    public function __construct(
        ?Model $office = null,
        ?array $dates = null,
    ) {
        if ($office) {
            $this->office($office);
        }

        if ($dates) {
            $this->dates($dates);
        }
    }

    public function __invoke(
        ?Model $office,
        ?array $dates,
        Collection|array|null $scanners = [],
        Collection|array|null $states = [],
        Collection|array|null $modes = [],
        Collection|array|null $status = [],
        Collection|array|null $substatus = [],
        bool $strict = false,
        string $size = 'folio',
        ?Signature $signature = null,
        #[SensitiveParameter]
        ?string $password = null,
    ): StreamedResponse {
        return $this->office($office)
            ->dates($dates)
            ->scanners($scanners)
            ->states($states)
            ->modes($modes)
            ->status($status)
            ->substatus($substatus)
            ->strict($strict)
            ->size($size)
            ->signature($signature)
            ->password($password)
            ->download();
    }

    public function password(#[SensitiveParameter] ?string $password): static
    {
        $this->password = is_string($password) ? fn (): string => $password : $password;

        return $this;
    }

    public function signature(?Signature $signature = null): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function office(Collection|Model $office): static
    {
        if ($office instanceof Collection) {
            $office->ensure([Group::class, Office::class]);
        }

        $this->office = $office;

        return $this;
    }

    public function dates(?array $dates): static
    {
        $this->dates = collect($dates)
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        return $this;
    }

    public function from(?string $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function to(?string $to): static
    {
        $this->to = $to;

        return $this;
    }

    public function scanners(Collection|array|null $scanners): static
    {
        $this->scanners = collect($scanners)->ensure(Scanner::class);

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

    public function status(Collection|array|null $status): static
    {
        $this->status = collect($status)
            ->map(fn ($status) => $status instanceof UnitEnum ? $status->value : $status)
            ->toArray();

        return $this;
    }

    public function substatus(Collection|array|null $substatus): static
    {
        $this->substatus = collect($substatus)
            ->map(fn ($substatus) => $substatus instanceof UnitEnum ? $substatus->value : $substatus)
            ->toArray();

        return $this;
    }

    public function strict(bool $strict = false): static
    {
        $this->strict = $strict;

        return $this;
    }

    public function transmittal(bool $transmittal = false): static
    {
        $this->transmittal = $transmittal;

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

    public function download(): StreamedResponse|BinaryFileResponse
    {
        if (! $this->signature && ! is_null($this->password)) {
            throw new InvalidArgumentException('Signature is required when password is provided');
        }

        $name = $this->filename().'.pdf';

        $headers = ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'"'];

        $downloadable = match ($this->password) {
            null => $this->pdf(),
            default => $this->signed()
        };

        return response()->streamDownload(fn () => print ($downloadable), $name, $headers);
    }

    protected function signed(): string
    {
        $export = $this->pdf(false);

        $name = $this->filename().'.pdf';

        $export->save(sys_get_temp_dir()."/$name");

        $certificate = (new ManageCert)->setPreservePfx()->fromPfx(storage_path('app/'.$this->signature->certificate), ($this->password)());

        try {
            return (new SignaturePdf(sys_get_temp_dir()."/$name", $certificate))->signature();
        } finally {
            if (file_exists(sys_get_temp_dir()."/$name")) {
                unlink(sys_get_temp_dir()."/$name");
            }
        }
    }

    protected function pdf(bool $base64 = true)
    {
        $pdf = Pdf::view($this->transmittal ? 'print.transmittal.attendance' : 'print.attendance', [
            'offices' => $this->office,
            'dates' => $this->dates,
            'from' => $this->from,
            'to' => $this->to,
            'scanners' => $this->scanners,
            'states' => $this->states,
            'modes' => $this->modes,
            'status' => $this->status,
            'substatus' => $this->substatus,
            'strict' => $this->strict,
            'signature' => $this->signature,
            'signed' => (bool) $this->password,
            'size' => $this->size,
        ]);

        $pdf->withBrowsershot(fn (Browsershot $browsershot) => $browsershot->noSandbox()->setOption('args', ['--disable-web-security']));

        match ($this->size) {
            'folio' => $pdf->paperSize(8.5, 13, 'in'),
            default => $pdf->format($this->size),
        };

        return $base64 ? base64_decode($pdf->base64()) : $pdf;
    }

    protected function filename(): string
    {
        $range = (function () {
            $dates = collect($this->dates)->map(fn ($date) => \Carbon\Carbon::parse($date))->unique()->sort();

            $formatted = $dates->groupBy(fn ($date) => $date->format('Y-m'))
                ->map(function ($dates) {
                    $days = $dates->map(fn ($date) => $date->format('d'))->sort()->toArray();

                    $formatted = (new NumberRangeCompressor)($days);

                    return implode(', ', $formatted).' '.$dates->first()->format('F Y');
                });

            return $formatted->join(', ', $formatted->count() > 2 ? ', and ' : ' and ');
        })();

        $prefix = 'Attendance'.($this->transmittal ? ' - Transmittal ' : ' ')."($range)";

        $title = $prefix.'('.collect($this->office)->map(fn ($o) => $o instanceof Office ? $o->code : $o->name)->join(',').')';

        return str($title)->limit(255)->toString();
    }

    public function toResponse($request): BinaryFileResponse|StreamedResponse
    {
        return $this->download();
    }
}
