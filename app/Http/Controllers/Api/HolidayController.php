<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), ['year' => 'required|date_format:Y']);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        ['year' => $year] = $validator->validated();

        $holidays = Holiday::whereYear('date', $year)
            ->select('date', 'name', 'remarks', 'type')
            ->orderBy('date')
            ->get();

        return response()->json($holidays);
    }
}
