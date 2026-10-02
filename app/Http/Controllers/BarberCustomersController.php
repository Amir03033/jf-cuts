<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Barbershop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarberCustomersController extends Controller
{
    public function index(Request $request): View
    {
        $barbershop = $this->barbershop();

        $search = trim((string) $request->input('search'));

        $customers = User::query()
            ->where('role', 'customer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->whereHas('appointments', function ($query) use ($barbershop) {
                $query->where('barbershop_id', $barbershop->id);
            })
            ->orderBy('name')
            ->get();

        return view('barber.customers', [
            'barbershop' => $barbershop,
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function show(User $customer): View
    {
        $barbershop = $this->barbershop();

        abort_unless(
            $customer->isCustomer(),
            404
        );

        $appointments = $barbershop->appointments()
            ->where('customer_id', $customer->id)
            ->with('service')
            ->orderByDesc('starts_at')
            ->get();

        $completedAppointments = $appointments->where(
            'status',
            AppointmentStatus::Completed
        );

        $visits = $completedAppointments->count();

        $totalReceived = (float) $completedAppointments->sum(
            fn ($appointment) => (float) $appointment->amount_paid
        );

        return view('barber.customer', [
            'barbershop' => $barbershop,
            'customer' => $customer,
            'appointments' => $appointments,
            'visits' => $visits,
            'totalReceived' => $totalReceived,
        ]);
    }

    private function barbershop(): Barbershop
    {
        return Barbershop::query()
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }
}
