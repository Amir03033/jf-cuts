<?php

namespace App\Http\Controllers;

use App\Models\Barbershop;
use App\Models\ShopImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BarberShopImageController extends Controller
{
    public function index(): View
    {
        $barbershop = $this->barbershop();

        $images = $barbershop->shopImages()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('barber.photos', [
            'barbershop' => $barbershop,
            'images' => $images,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $barbershop = $this->barbershop();

        $data = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $lastSortOrder = $barbershop->shopImages()
            ->max('sort_order');

        $path = $request->file('image')->store(
            'barbershops/' . $barbershop->id,
            'public'
        );

        $barbershop->shopImages()->create([
            'path' => $path,
            'sort_order' => ((int) $lastSortOrder) + 1,
        ]);

        return redirect()
            ->route('barber.photos')
            ->with('status', 'De foto is toegevoegd.');
    }

    public function destroy(ShopImage $image): RedirectResponse
    {
        $barbershop = $this->barbershop();

        abort_unless(
            $image->barbershop_id === $barbershop->id,
            403
        );

        Storage::disk('public')->delete($image->path);

        $image->delete();

        return redirect()
            ->route('barber.photos')
            ->with('status', 'De foto is verwijderd.');
    }

    private function barbershop(): Barbershop
    {
        return Barbershop::query()
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }
}
