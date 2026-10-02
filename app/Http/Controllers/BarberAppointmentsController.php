<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Exceptions\BookingException;

class BarberAppointmentsController extends Controller
{
    public function index(): View
    {
        $barbershop = $this->barbershop();

        $appointments = $barbershop->appointments()
            ->with(['customer', 'service'])
            ->orderBy('starts_at')
            ->get();

        return view('barber.appointments', [
            'barbershop' => $barbershop,
            'appointments' => $appointments,
        ]);
    }

    public function cancel(
        Appointment $appointment,
        AppointmentService $appointmentService
    ): RedirectResponse {
        $this->authorizeAppointment($appointment);

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
            'De afspraak is geannuleerd.'
        );
    }

    public function complete(
        Request $request,
        Appointment $appointment,
        AppointmentService $appointmentService
    ): RedirectResponse {
        $this->authorizeAppointment($appointment);

        $data = $request->validate([
            'amount_paid' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        try {
            $appointmentService->completeAppointment(
                $appointment,
                auth()->user(),
                $data['amount_paid']
            );
        } catch (BookingException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return back()->with(
            'status',
            'De afspraak is voltooid.'
        );
    }

    public function noShow(
        Appointment $appointment,
        AppointmentService $appointmentService
    ): RedirectResponse {
        $this->authorizeAppointment($appointment);

        try {
            $appointmentService->markAsNoShow(
                $appointment,
                auth()->user()
            );
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'status',
            'De afspraak is als no-show gemarkeerd.'
        );
    }

    private function barbershop(): Barbershop
    {
        return Barbershop::query()
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    private function authorizeAppointment(
        Appointment $appointment
    ): void {
        if (
            $appointment->barbershop->owner_id !== auth()->id()
        ) {
            abort(403);
        }
    }
}
