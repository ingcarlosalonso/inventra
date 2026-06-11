<?php

namespace App\Imports;

use App\Models\Barcode;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\ProductType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToCollection, WithHeadingRow, WithValidation
{
    public array $errors = [];

    public int $imported = 0;

    public int $updated = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            try {
                $this->processRow($row->toArray());
            } catch (\Throwable $e) {
                $this->errors[] = "Fila {$rowNum}: {$e->getMessage()}";
            }
        }
    }

    private function processRow(array $row): void
    {
        $name = trim($row['nombre'] ?? $row['name'] ?? '');
        if (! $name) {
            return;
        }

        $productTypeName = trim($row['tipo'] ?? $row['type'] ?? '');
        $productType = ProductType::where('name', $productTypeName)->first();
        if (! $productType && $productTypeName) {
            $productType = ProductType::create(['name' => $productTypeName, 'is_active' => true]);
        }

        $barcode = trim($row['codigo_barras'] ?? $row['barcode'] ?? '');

        // Try to find existing product by barcode first, then by name
        $product = null;
        if ($barcode) {
            $barcodeModel = Barcode::where('barcode', $barcode)->first();
            $product = $barcodeModel?->product;
        }
        if (! $product) {
            $product = Product::where('name', $name)->first();
        }

        if ($product) {
            $product->update([
                'name' => $name,
                'product_type_id' => $productType?->id ?? $product->product_type_id,
                'cost' => isset($row['costo']) ? (float) $row['costo'] : (isset($row['cost']) ? (float) $row['cost'] : $product->cost),
                'is_active' => true,
            ]);
            $this->updated++;
        } else {
            $product = Product::create([
                'name' => $name,
                'product_type_id' => $productType?->id,
                'cost' => isset($row['costo']) ? (float) $row['costo'] : (isset($row['cost']) ? (float) $row['cost'] : 0),
                'is_active' => true,
            ]);
            $this->imported++;
        }

        if ($barcode) {
            Barcode::firstOrCreate(['product_id' => $product->id, 'barcode' => $barcode]);
        }

        // Update/create the first presentation (default) if price/stock provided
        $price = isset($row['precio']) ? (float) $row['precio'] : (isset($row['price']) ? (float) $row['price'] : null);
        $stock = isset($row['stock']) ? (float) $row['stock'] : null;
        $minStock = isset($row['stock_minimo']) ? (float) $row['stock_minimo'] : (isset($row['min_stock']) ? (float) $row['min_stock'] : null);

        if ($price !== null || $stock !== null) {
            $presentation = Presentation::first();
            if ($presentation) {
                $pp = ProductPresentation::where('product_id', $product->id)
                    ->where('presentation_id', $presentation->id)
                    ->first();

                if ($pp) {
                    $pp->update(array_filter([
                        'price' => $price,
                        'stock' => $stock,
                        'min_stock' => $minStock,
                    ], fn ($v) => $v !== null));
                } else {
                    ProductPresentation::create([
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'price' => $price ?? 0,
                        'stock' => $stock ?? 0,
                        'min_stock' => $minStock ?? 0,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [];
    }
}
