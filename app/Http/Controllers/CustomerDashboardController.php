<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $nextAppointment = $user->appointments()
            ->upcoming()
            ->with('service')
            ->orderBy('starts_at')
            ->first();

        $visits = $user->appointments()
            ->completed()
            ->count();

        return view('customer.dashboard', [
            'greeting' => $this->greeting(),
            'firstName' => Str::before($user->name, ' '),
            'nextAppointment' => $nextAppointment,
            'visits' => $visits,
        ]);
    }

    private function greeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'Goedemorgen',
            now()->hour < 18 => 'Goedemiddag',
            default => 'Goedenavond',
        };
    }
}
