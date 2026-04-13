<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Models\Currency;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Product\Scopes\BySearch;
use App\Models\ProductType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $query = Product::with([
            'productType',
            'barcodes',
            'currency',
            'productPresentations.presentation.presentationType',
        ]);

        if ($request->filled('search')) {
            $query->withScopes(new BySearch($request->string('search')));
        }

        return ProductResource::collection(
            $query->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $barcodes = $data['barcodes'] ?? [];
        $presentations = $data['presentations'];
        unset($data['barcodes'], $data['presentations']);

        $data['product_type_id'] = $this->resolveId(ProductType::class, $data['product_type_id']);
        $data['currency_id'] = $this->resolveId(Currency::class, $data['currency_id'] ?? null);

        $product = Product::create($data);

        if ($barcodes) {
            $product->barcodes()->createMany(
                array_map(fn (string $barcode) => ['barcode' => $barcode], $barcodes)
            );
        }

        $this->syncPresentations($product, $presentations);

        return ProductResource::make(
            $product->load(['productType', 'barcodes', 'currency', 'productPresentations.presentation.presentationType'])
        )->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();
        $barcodes = $data['barcodes'] ?? [];
        $presentations = $data['presentations'];
        unset($data['barcodes'], $data['presentations']);

        $data['product_type_id'] = $this->resolveId(ProductType::class, $data['product_type_id']);
        $data['currency_id'] = $this->resolveId(Currency::class, $data['currency_id'] ?? null);

        $product->update($data);

        $product->barcodes()->delete();
        if ($barcodes) {
            $product->barcodes()->createMany(
                array_map(fn (string $barcode) => ['barcode' => $barcode], $barcodes)
            );
        }

        $this->syncPresentations($product, $presentations);

        return ProductResource::make(
            $product->fresh()->load(['productType', 'barcodes', 'currency', 'productPresentations.presentation.presentationType'])
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([], 204);
    }

    public function toggle(Product $product): ProductResource
    {
        $product->update(['is_active' => ! $product->is_active]);

        return ProductResource::make(
            $product->fresh()->load(['productType', 'barcodes', 'currency', 'productPresentations.presentation.presentationType'])
        );
    }

    private function syncPresentations(Product $product, array $presentations): void
    {
        $product->productPresentations()->delete();

        foreach ($presentations as $item) {
            $product->productPresentations()->create([
                'presentation_id' => Presentation::where('uuid', $item['presentation_id'])->value('id'),
                'price' => $item['price'],
                'min_stock' => $item['min_stock'],
                'stock' => $item['stock'] ?? 0,
            ]);
        }
    }

    private function resolveId(string $model, ?string $uuid): ?int
    {
        if (! $uuid) {
            return null;
        }

        return $model::where('uuid', $uuid)->value('id');
    }
}
