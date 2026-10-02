<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Barbershop;
use App\Services\RevenueService;
use Illuminate\View\View;

class BarberDashboardController extends Controller
{
    public function index(RevenueService $revenueService): View
    {
        $barbershop = Barbershop::query()
            ->where('owner_id', auth()->id())
            ->first();

        if (! $barbershop) {
            return view('barber.dashboard', [
                'barbershop' => null,
                'revenueWeek' => 0,
                'revenueMonth' => 0,
                'revenueYear' => 0,
                'upcomingAppointments' => collect(),
                'todayAppointments' => collect(),
            ]);
        }

        $upcomingAppointments = $barbershop->appointments()
            ->where('starts_at', '>=', now())
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ])
            ->with(['customer', 'service'])
            ->orderBy('starts_at')
            ->take(5)
            ->get();

        $todayAppointments = $barbershop->appointments()
            ->whereBetween('starts_at', [
                now()->startOfDay(),
                now()->endOfDay(),
            ])
            ->with(['customer', 'service'])
            ->orderBy('starts_at')
            ->get();

        return view('barber.dashboard', [
            'barbershop' => $barbershop,
            'revenueWeek' => $revenueService->week($barbershop),
            'revenueMonth' => $revenueService->month($barbershop),
            'revenueYear' => $revenueService->year($barbershop),
            'upcomingAppointments' => $upcomingAppointments,
            'todayAppointments' => $todayAppointments,
        ]);
    }
}
