<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DebtItem;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OwnerProductController extends Controller
{
    private const MAX_PRODUCTS_PER_REQUEST = 50;

    public function index(Request $request): View
    {
        return view('owner.products', [
            'products' => $this->products($request->string('q')->toString()),
            'q' => trim($request->string('q')->toString()),
        ]);
    }

    /**
     * Return only the products list fragment so the page can stay
     * up-to-date without a full reload. Accepts an optional ?q= search
     * term used by the live search box.
     */
    public function list(Request $request): View
    {
        return view('components.owner.products-list', [
            'products' => $this->products($request->string('q')->toString()),
            'q' => trim($request->string('q')->toString()),
        ]);
    }

    /**
     * Save multiple products in a single transaction. Every product is
     * inserted through Eloquent (bound parameters, no raw SQL) and gets an
     * initial price history row so the price trail stays consistent.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'products' => ['required', 'array', 'min:1', 'max:'.self::MAX_PRODUCTS_PER_REQUEST],
            'products.*.product_name' => ['required', 'string', 'max:150'],
            'products.*.description' => ['nullable', 'string', 'max:5000'],
            'products.*.price' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ]);

        $user = $this->requiredStoreOwner();

        $saved = [];

        DB::transaction(function () use ($validated, $user, &$saved) {
            foreach ($validated['products'] as $row) {
                $product = Product::create([
                    'product_name' => trim($row['product_name']),
                    'description' => $this->normalizeDescription($row['description'] ?? null),
                    'price' => $row['price'],
                    'created_by' => $user->user_id,
                ]);

                ProductPriceHistory::create([
                    'product_id' => $product->product_id,
                    'price' => $product->price,
                    'effective_from' => now(),
                    'changed_by' => $user->user_id,
                ]);

                $this->log($user->user_id, 'create', $product->product_id, null, [
                    'product_name' => $product->product_name,
                    'description' => $product->description,
                    'price' => $product->price,
                ]);

                $saved[] = $product->fresh();
            }
        });

        return response()->json([
            'message' => count($saved).' product(s) saved.',
            'products' => $this->productPayload($saved),
        ], 201);
    }

    /**
     * Update a single product. When the price changes, the active price
     * history row is closed and a new one is opened so the price trail
     * reflects the edit. Changes are recorded on the activity log.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ]);

        $user = $this->requiredStoreOwner();

        $oldValues = $product->only(['product_name', 'description', 'price']);
        $oldPrice = number_format((float) $product->price, 2, '.', '');
        $newPrice = number_format((float) $validated['price'], 2, '.', '');

        DB::transaction(function () use ($product, $validated, $user, $oldPrice, $newPrice, $oldValues) {
            $product->update([
                'product_name' => trim($validated['product_name']),
                'description' => $this->normalizeDescription($validated['description'] ?? null),
                'price' => $validated['price'],
            ]);

            if ($newPrice !== $oldPrice) {
                ProductPriceHistory::query()
                    ->where('product_id', $product->product_id)
                    ->whereNull('effective_to')
                    ->update(['effective_to' => now()]);

                ProductPriceHistory::create([
                    'product_id' => $product->product_id,
                    'price' => $validated['price'],
                    'effective_from' => now(),
                    'changed_by' => $user->user_id,
                ]);
            }

            $this->log($user->user_id, 'update', $product->product_id, $oldValues, [
                'product_name' => $product->product_name,
                'description' => $product->description,
                'price' => $product->price,
            ]);
        });

        return response()->json([
            'message' => 'Product updated.',
            'product' => (array) head($this->productPayload([$product->fresh()])),
        ]);
    }

    /**
     * Delete a product. Products referenced by a debt item cannot be removed;
     * the caller gets a 409 so the UI can explain why.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        if (DebtItem::query()
            ->where('product_id', $product->product_id)
            ->exists()) {
            return response()->json([
                'message' => 'This product is used in a debt item and cannot be deleted.',
            ], 409);
        }

        $user = $this->requiredStoreOwner();

        $oldValues = $product->only(['product_name', 'description', 'price']);

        DB::transaction(function () use ($product, $user, $oldValues) {
            $this->log($user->user_id, 'delete', $product->product_id, $oldValues, null);

            ProductPriceHistory::query()
                ->where('product_id', $product->product_id)
                ->delete();

            $product->delete();
        });

        return response()->json([
            'message' => 'Product deleted.',
        ]);
    }

    /**
     * Resolve the acting owner. The owner route middleware has already
     * verified the session belongs to an existing store owner account, so
     * the id is never taken from the request body and a caller cannot
     * impersonate a user.
     */
    private function requiredStoreOwner(): User
    {
        return User::findOrFail(session('owner_id'));
    }

    private function products(?string $query = null)
    {
        $term = $query !== null ? trim($query) : '';

        return Product::query()
            ->when($term !== '', function ($builder) use ($term) {
                $pattern = '%'.addcslashes($term, '%_').'%';

                $builder->where(function ($sub) use ($pattern) {
                    $sub->where('product_name', 'LIKE', $pattern)
                        ->orWhere('description', 'LIKE', $pattern);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('product_name')
            ->limit(200)
            ->get();
    }

    private function productPayload(array $products): array
    {
        return collect($products)->map(function (Product $product) {
            return [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'description' => $product->description,
                'price' => $product->price,
                'created_at' => $product->created_at?->toISOString(),
            ];
        })->values()->toArray();
    }

    private function normalizeDescription(?string $value): ?string
    {
        $description = trim((string) $value);

        return $description !== '' ? $description : null;
    }

    private function log(string $userId, string $action, string $recordId, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => 'products',
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
}
