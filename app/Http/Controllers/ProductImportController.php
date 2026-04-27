<?php

namespace App\Http\Controllers;

use App\Imports\ProductImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new ProductImport;
        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->imported,
            'updated' => $import->updated,
            'errors' => $import->errors,
        ]);
    }
}
