<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerAppointmentsController extends Controller
{
    public function index(): View
    {
        $appointments = Appointment::query()
            ->where('customer_id', auth()->id())
            ->with(['service', 'barbershop'])
            ->orderByDesc('starts_at')
            ->get();

        return view('customer.appointments', [
            'appointments' => $appointments,
        ]);
    }

    public function cancel(
        Appointment $appointment,
        AppointmentService $appointmentService
    ): RedirectResponse {
        if ($appointment->customer_id !== auth()->id()) {
            abort(403);
        }

        try {
            $appointmentService->cancelAppointment(
                $appointment,
                auth()->user()
            );
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'status',
            'Je afspraak is geannuleerd.'
        );
    }
}

