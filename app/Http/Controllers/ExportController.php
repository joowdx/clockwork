<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class ExportController extends Controller
{
    protected Request $request;

    public function __invoke(Request $request, string $mode)
    {
        $this->request = $request;

        return $this->$mode();
    }

    public function timesheet()
    {
        $validator = Validator::make($this->request->all(), [
            'period' => ['required', 'in:full,1st,2nd,overtime'],
            'month' => ['required', 'date_format:Y-m'],
            'format' => ['string', 'in:folio,letter,a4'],
            'uid.*' => ['string', 'exists:employees,uid'],
            'uid' => ['required', 'min:1', 'max:100', 'exists:employees,uid'],
            'download' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $params = $validator->validated();

        $params['uid'] = is_array($params['uid']) ? $params['uid'] : [$params['uid']];

        $timesheets = Timesheet::query()
            ->whereHas('employee', fn ($query) => $query->whereIn('uid', $params['uid']))
            ->with(['timetables', 'employee:id,name'])
            ->lazy();

        $timesheets = match ($params['period']) {
            '1st' => $timesheets->map(fn ($timesheet) => $timesheet->setFirstHalf()),
            '2nd' => $timesheets->map(fn ($timesheet) => $timesheet->setSecondHalf()),
            'overtime' => $timesheets->map(fn ($timesheet) => $timesheet->overtime()),
            default => $timesheets,
        };

        $name = substr(implode('-', $params['uid']), 0, 60).'.pdf';

        $headers = ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'"'];

        $pdf = $this->pdf('print.csc', ['format' => $params['format'] ?? 'folio', 'timesheets' => $timesheets]);

        return $params['download'] ?? true
            ? response()->streamDownload(fn () => print (base64_decode($pdf->base64())), $name, $headers)
            : $pdf->inline($name);
    }

    protected function pdf(string $view, array $data): PdfBuilder
    {
        $pdf = Pdf::view($view, $data)
            ->withBrowsershot(fn ($bs) => $bs->noSandbox()->setOption('args', ['--disable-web-security']));

        if (($data['format'] ??= 'a4') === 'folio') {
            $pdf->paperSize(8.5, 13, 'in');
        } else {
            $pdf->format($data['format']);
        }

        return $pdf;
    }
}
