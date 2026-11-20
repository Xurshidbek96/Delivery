<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ShopResource;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Katalog: mijozga faqat faol do'konlar va ularning faol mahsulotlari ko'rinadi (nofaoli — 404). */
class CatalogController extends ApiController
{
    public function shops(Request $request): AnonymousResourceCollection
    {
        $shops = Shop::query()
            ->active()
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', '%'.$this->escapeLike($term).'%'))
            ->ordered()
            ->paginate($this->perPage($request));

        return ShopResource::collection($shops);
    }

    public function shop(int $shop): ShopResource
    {
        return new ShopResource(
            Shop::query()->active()->withCount(['products' => fn ($q) => $q->where('is_active', true)])->findOrFail($shop)
        );
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'shop_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $products = Product::query()
            ->visible()
            ->with('shop')
            ->when($request->integer('shop_id'), fn ($q, $shopId) => $q->where('shop_id', $shopId))
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', '%'.$this->escapeLike($term).'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ProductResource::collection($products);
    }

    public function product(int $product): ProductResource
    {
        return new ProductResource(Product::query()->visible()->with('shop')->findOrFail($product));
    }

    private function escapeLike(string $term): string
    {
        return addcslashes($term, '%_\\');
    }
}
