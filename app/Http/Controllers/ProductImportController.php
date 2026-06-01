<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductImport\StoreProductImportRequest;
use App\Imports\ProductImport;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportController extends Controller
{
    public function store(StoreProductImportRequest $request): JsonResponse
    {
        $import = new ProductImport;
        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->imported,
            'updated' => $import->updated,
            'errors' => $import->errors,
        ]);
    }
}
