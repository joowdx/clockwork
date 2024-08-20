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
                    'id' => strtolower(str()->ulid()),
                    'name' => [
                        'last' => mb_convert_encoding($entry[$headers['last name']], 'UTF-8', 'auto'),
                        'first' => mb_convert_encoding($entry[$headers['first name']], 'UTF-8', 'auto'),
                        'middle' => mb_convert_encoding(@$entry[$headers['middle name']], 'UTF-8', 'auto'),
                        'extension' => mb_convert_encoding(@$entry[$headers['name extension']], 'UTF-8', 'auto'),
                    ],
                    'office' => @$entry[$headers['office']],
                    'regular' => (bool) @$entry[$headers['regular']],
                    'csc_format' => @$entry[$headers['csc format']] === null ? true : filter_var(@$entry[$headers['csc format']], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
                    'toJSON' => true,
                    'groups' => explode(',', mb_convert_encoding(@$entry[$headers['groups']] ?? '', 'UTF-8', 'auto')),
                ],
                'scanners' => $this->uids($entry, $this->scanners($headers)),
            ];
        }));
    }
}
