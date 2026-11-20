<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with('shop')
            ->when($request->integer('shop_id'), fn ($q, $shopId) => $q->where('shop_id', $shopId))
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->query('state') === 'visible', fn ($q) => $q->visible())
            ->when($request->query('state') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('shop_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.products.index', [
            'title' => 'Mahsulotlar',
            'products' => $products,
            'shops' => Shop::query()->ordered()->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form('Yangi mahsulot', null, $request->integer('shop_id') ?: null);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product($request->productData());
        $product->replaceImage($request->file('image'));
        $product->save();

        return redirect()->route('admin.shops.show', $product->shop_id)->with('success', 'Mahsulot qo\'shildi.');
    }

    public function edit(Product $product): View
    {
        return $this->form('Mahsulotni tahrirlash', $product);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->fill($request->productData());
        $product->replaceImage($request->file('image'), $request->boolean('remove_image'));
        $product->save();

        return redirect()->route('admin.shops.show', $product->shop_id)->with('success', 'Saqlandi.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active ? 'Mahsulot faollashtirildi.' : 'Mahsulot mijozlardan yashirildi.');
    }

    /** Buyurtmalardagi nusxa (nom, do'kon nomi) saqlanib qoladi. */
    public function destroy(Product $product): RedirectResponse
    {
        $shopId = $product->shop_id;
        $product->deleteImage();
        $product->delete();

        return redirect()->route('admin.shops.show', $shopId)->with('success', 'Mahsulot o\'chirildi.');
    }

    private function form(string $title, ?Product $product, ?int $shopId = null): View
    {
        return view('admin.products.form', [
            'title' => $title,
            'product' => $product,
            'shopId' => $product?->shop_id ?? $shopId,
            'shops' => Shop::query()->ordered()->get(['id', 'name', 'is_active']),
            'units' => ItemUnit::cases(),
        ]);
    }
}
