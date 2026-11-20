<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShopRequest;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $shops = Shop::query()
            ->withCount(['products', 'products as active_products_count' => fn ($q) => $q->where('is_active', true)])
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('address', 'like', "%{$term}%")))
            ->when($request->query('state') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->query('state') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.shops.index', ['title' => 'Do\'konlar', 'shops' => $shops]);
    }

    public function create(): View
    {
        return view('admin.shops.form', ['title' => 'Yangi do\'kon', 'shop' => null]);
    }

    public function store(ShopRequest $request): RedirectResponse
    {
        $shop = new Shop($request->shopData());
        $shop->replaceImage($request->file('image'));
        $shop->save();

        return redirect()->route('admin.shops.show', $shop)->with('success', 'Do\'kon qo\'shildi. Endi unga mahsulot qo\'shing.');
    }

    public function show(Request $request, Shop $shop): View
    {
        return view('admin.shops.show', [
            'title' => $shop->name,
            'shop' => $shop,
            'products' => $shop->products()->with('shop')->orderBy('sort_order')->orderBy('name')->paginate(30),
        ]);
    }

    public function edit(Shop $shop): View
    {
        return view('admin.shops.form', ['title' => 'Do\'konni tahrirlash', 'shop' => $shop]);
    }

    public function update(ShopRequest $request, Shop $shop): RedirectResponse
    {
        $shop->fill($request->shopData());
        $shop->replaceImage($request->file('image'), $request->boolean('remove_image'));
        $shop->save();

        return redirect()->route('admin.shops.show', $shop)->with('success', 'Saqlandi.');
    }

    public function toggle(Shop $shop): RedirectResponse
    {
        $shop->update(['is_active' => ! $shop->is_active]);

        return back()->with('success', $shop->is_active ? 'Do\'kon mijozlarga ko\'rinadi.' : 'Do\'kon va uning mahsulotlari mijozlardan yashirildi.');
    }

    public function destroy(Shop $shop): RedirectResponse
    {
        if ($shop->products()->exists()) {
            throw new BusinessRuleException('Do\'konda mahsulotlar bor. Avval ularni o\'chiring yoki do\'konni nofaol qiling.');
        }

        $shop->deleteImage();
        $shop->delete();

        return redirect()->route('admin.shops.index')->with('success', 'Do\'kon o\'chirildi.');
    }
}
