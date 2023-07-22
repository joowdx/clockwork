<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchRequest;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SearchRequest $search)
    {
        return response()->json([
            'employees' => $search->employees,
            'date' => $search->date,
        ]);
    }
}
