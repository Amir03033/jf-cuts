<?php

namespace App\Http\Controllers;

use App\Models\Barbershop;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarberServiceController extends Controller
{
    public function index(): View
    {
        $barbershop = $this->barbershop();

        $services = $barbershop->services()
            ->orderBy('active', 'desc')
            ->orderBy('name')
            ->get();

        return view('barber.services', [
            'barbershop' => $barbershop,
            'services' => $services,
        ]);
    }

    public function create(): View
    {
        $barbershop = $this->barbershop();

        return view('barber.service-create', [
            'barbershop' => $barbershop,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $barbershop = $this->barbershop();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $barbershop->services()->create([
            'name' => $data['name'],
            'duration' => $data['duration'],
            'price' => $data['price'],
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('barber.services')
            ->with('status', 'De dienst is toegevoegd.');
    }

    public function edit(Service $service): View
    {
        $this->authorizeService($service);

        return view('barber.service-edit', [
            'barbershop' => $service->barbershop,
            'service' => $service,
        ]);
    }

    public function update(
        Request $request,
        Service $service
    ): RedirectResponse {
        $this->authorizeService($service);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $service->update([
            'name' => $data['name'],
            'duration' => $data['duration'],
            'price' => $data['price'],
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('barber.services')
            ->with('status', 'De dienst is aangepast.');
    }

    private function barbershop(): Barbershop
    {
        return Barbershop::query()
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    private function authorizeService(Service $service): void
    {
        if ($service->barbershop->owner_id !== auth()->id()) {
            abort(403);
        }
    }
}
