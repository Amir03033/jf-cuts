<?php

namespace App\Http\Controllers;

use App\Models\Barbershop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarberSettingsController extends Controller
{
    public function edit(): View
    {
        $barbershop = $this->barbershop();

        $settings = $barbershop->settings;

        $availabilityRules = $barbershop->availabilityRules()
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week');

        return view('barber.settings', [
            'barbershop' => $barbershop,
            'settings' => $settings,
            'availabilityRules' => $availabilityRules,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $barbershop = $this->barbershop();

        $data = $request->validate([
            'booking_interval' => [
                'required',
                'integer',
                'min:5',
                'max:240',
            ],

            'max_booking_days' => [
                'required',
                'integer',
                'min:1',
                'max:365',
            ],

            'cancellation_limit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'reschedule_limit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'opening_hours' => [
                'required',
                'array',
            ],

            'opening_hours.*.is_available' => [
                'nullable',
                'boolean',
            ],

            'opening_hours.*.start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'opening_hours.*.end_time' => [
                'nullable',
                'date_format:H:i',
            ],
        ]);

        $barbershop->settings()->updateOrCreate(
            [],
            [
                'booking_interval' => $data['booking_interval'],
                'max_booking_days' => $data['max_booking_days'],
                'cancellation_limit' => $data['cancellation_limit'],
                'reschedule_limit' => $data['reschedule_limit'],
            ]
        );

        foreach ($data['opening_hours'] as $day => $hours) {
            $isAvailable = $request->boolean(
                "opening_hours.{$day}.is_available"
            );

            $startTime = $hours['start_time'] ?? null;
            $endTime = $hours['end_time'] ?? null;

            if ($isAvailable && (!$startTime || !$endTime)) {
                return back()
                    ->withErrors([
                        "opening_hours.{$day}.start_time" =>
                            'Vul een begin- en eindtijd in.',
                    ])
                    ->withInput();
            }

            if (
                $isAvailable &&
                $startTime &&
                $endTime &&
                $startTime >= $endTime
            ) {
                return back()
                    ->withErrors([
                        "opening_hours.{$day}.end_time" =>
                            'De eindtijd moet na de begintijd liggen.',
                    ])
                    ->withInput();
            }

            $barbershop->availabilityRules()->updateOrCreate(
                [
                    'day_of_week' => $day,
                ],
                [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_available' => $isAvailable,
                ]
            );
        }

        return redirect()
            ->route('barber.settings')
            ->with('status', 'De instellingen zijn opgeslagen.');
    }

    private function barbershop(): Barbershop
    {
        return Barbershop::query()
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }
}
