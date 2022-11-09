<?php

namespace App\Pipes;

use App\Traits\ParsesEmployeeImport;

class TransformEmployeeScannerData
{
    use ParsesEmployeeImport;

    public function handle(mixed $request, \Closure $next)
    {
        $headers = $this->headers($request->first());

        return $next($request->skip(1)->map(function (array $entry) use ($headers) {
            return [
                'employee' => [
                    'id' => str()->orderedUuid()->toString(),
                    'name' => [
                        'last' => $entry[$headers['last name']],
                        'first' => $entry[$headers['first name']],
                        'middle' => @$entry[$headers['middle name']],
                        'extension' => @$entry[$headers['name extension']],
                    ],
                    'office' => @$entry[$headers['office']],
                    'regular' => (bool) @$entry[$headers['regular']],
                    'csc_format' => @$entry[$headers['csc format']] === null ? true : filter_var(@$entry[$headers['csc format']], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
                    'nameToJSON' => true,
                ],
                'scanners' => $this->uids($entry, $this->scanners($headers)),
            ];
        }));
    }
}
