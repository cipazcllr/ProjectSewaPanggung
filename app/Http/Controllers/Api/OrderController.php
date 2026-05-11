<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
        ]);

        $limit = $validated['limit'] ?? 10;
        $page = $validated['page'] ?? 1;
        $query = Order::with('items.product');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['date'])) {
            $query->whereDate('date', $validated['date']);
        }

        $total = (clone $query)->count();
        $data = $query->latest('date')->latest('id')->forPage($page, $limit)->get();

        return response()->json(compact('data', 'total', 'page'));
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load('items.product'));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatedOrder($request);

        $order = DB::transaction(function () use ($payload) {
            $this->assertStockAvailable($payload['items']);

            $total = $this->totalFromItems($payload['items']);
            $order = Order::create([
                'order_number' => $this->nextOrderNumber(),
                'customer' => $payload['customer'],
                'date' => $payload['date'],
                'status' => $payload['status'] ?? 'Pending',
                'total' => $total,
            ]);

            $this->syncItems($order, $payload['items'], reduceStock: true);

            return $order->load('items.product');
        });

        return response()->json($order, 201);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $payload = $this->validatedOrder($request, partial: true);

        $order = DB::transaction(function () use ($payload, $order) {
            $data = collect($payload)->only(['customer', 'date', 'status'])->all();

            if (isset($payload['items'])) {
                $this->restoreStock($order);
                $this->assertStockAvailable($payload['items']);
                $order->items()->delete();
                $this->syncItems($order, $payload['items'], reduceStock: true);
                $data['total'] = $this->totalFromItems($payload['items']);
            }

            $order->update($data);

            return $order->refresh()->load('items.product');
        });

        return response()->json($order);
    }

    public function destroy(Order $order): JsonResponse
    {
        DB::transaction(function () use ($order) {
            $this->restoreStock($order);
            $order->delete();
        });

        return response()->json(['success' => true]);
    }

    public function exportPdf(Request $request): Response
    {
        $validated = $request->validate([
            'orderId' => ['required', 'string'],
        ]);

        $order = Order::with('items.product')
            ->where('order_number', $validated['orderId'])
            ->when(is_numeric($validated['orderId']), fn ($query) => $query->orWhere('id', $validated['orderId']))
            ->firstOrFail();

        $lines = [
            'Order: '.$order->order_number,
            'Customer: '.$order->customer,
            'Date: '.$order->date->format('Y-m-d'),
            'Status: '.$order->status,
            'Total: '.$order->total,
            '',
            'Items:',
        ];

        foreach ($order->items as $item) {
            $lines[] = sprintf(
                '- %s x%s @ %s = %s',
                $item->product?->name ?? 'Product #'.$item->product_id,
                $item->qty,
                $item->price,
                $item->subtotal
            );
        }

        return response($this->makeSimplePdf($lines), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$order->order_number.'.pdf"',
        ]);
    }

    public function validateStock(Request $request): JsonResponse
    {
        $items = $request->validate([
            '*.productId' => ['required', 'integer', 'exists:products,id'],
            '*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $errors = $this->stockErrors($items);

        return response()->json([
            'available' => count($errors) === 0,
            'errors' => $errors,
        ]);
    }

    private function validatedOrder(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'customer' => [$required, 'string', 'max:255'],
            'date' => [$required, 'date'],
            'status' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:50'],
            'items' => [$partial ? 'sometimes' : 'required', 'array', 'min:1'],
            'items.*.productId' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],
        ]);
    }

    private function assertStockAvailable(array $items): void
    {
        $errors = $this->stockErrors($items);

        if ($errors !== []) {
            abort(response()->json([
                'message' => 'Stok tidak cukup.',
                'errors' => $errors,
            ], 422));
        }
    }

    private function stockErrors(array $items): array
    {
        $errors = [];
        $requested = [];

        foreach ($items as $item) {
            $requested[$item['productId']] = ($requested[$item['productId']] ?? 0) + $item['qty'];
        }

        $products = Product::whereIn('id', array_keys($requested))->get()->keyBy('id');

        foreach ($requested as $productId => $qty) {
            $product = $products->get($productId);

            if (! $product || $product->stock < $qty) {
                $errors[] = [
                    'productId' => $productId,
                    'requested' => $qty,
                    'available' => $product?->stock ?? 0,
                ];
            }
        }

        return $errors;
    }

    private function syncItems(Order $order, array $items, bool $reduceStock): void
    {
        foreach ($items as $item) {
            $subtotal = $item['qty'] * $item['price'];
            $order->items()->create([
                'product_id' => $item['productId'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
            ]);

            if ($reduceStock) {
                Product::whereKey($item['productId'])->decrement('stock', $item['qty']);
            }
        }
    }

    private function restoreStock(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            Product::whereKey($item->product_id)->increment('stock', $item->qty);
        }
    }

    private function totalFromItems(array $items): float
    {
        return array_sum(array_map(fn (array $item) => $item['qty'] * $item['price'], $items));
    }

    private function nextOrderNumber(): string
    {
        return 'ORD-'.str_pad((string) (Order::max('id') + 1), 3, '0', STR_PAD_LEFT);
    }

    private function makeSimplePdf(array $lines): string
    {
        $content = "BT\n/F1 12 Tf\n50 780 Td\n";

        foreach ($lines as $index => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= ($index === 0 ? '' : "0 -18 Td\n").'('.$escaped.") Tj\n";
        }

        $content .= 'ET';
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }
}
