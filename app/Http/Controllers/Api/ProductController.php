<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string'],
            'sort' => ['nullable', 'in:id,name,price,stock,created_at'],
        ]);

        $limit = $validated['limit'] ?? 10;
        $page = $validated['page'] ?? 1;
        $query = Product::query();

        if (! empty($validated['search'])) {
            $query->where('name', 'like', '%'.$validated['search'].'%');
        }

        $total = (clone $query)->count();
        $data = $query
            ->orderBy($validated['sort'] ?? 'id')
            ->forPage($page, $limit)
            ->get();

        return response()->json(compact('data', 'total', 'page'));
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->validatedProduct($request));

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($this->validatedProduct($request));

        return response()->json($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['success' => true]);
    }

    public function bulkUpdateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            '*.productId' => ['required', 'integer', 'exists:products,id'],
            '*.qty' => ['required', 'integer'],
        ]);

        $updated = DB::transaction(function () use ($validated) {
            foreach ($validated as $item) {
                Product::whereKey($item['productId'])->increment('stock', $item['qty']);
            }

            return count($validated);
        });

        return response()->json([
            'success' => true,
            'updated' => $updated,
        ]);
    }

    private function validatedProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);
    }
}
