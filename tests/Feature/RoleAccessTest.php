<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/barber/dashboard')->assertRedirect(route('login'));
    }

    public function test_customer_cannot_open_barber_dashboard(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/barber/dashboard')->assertForbidden();
    }

    public function test_barber_can_open_barber_dashboard(): void
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($barber)->get('/barber/dashboard')->assertOk();
    }

    public function test_barber_cannot_open_customer_dashboard(): void
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($barber)->get('/customer/dashboard')->assertForbidden();
    }

    public function test_dashboard_redirects_by_role(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')->assertRedirect(route('customer.dashboard'));

        $this->actingAs(User::factory()->barber()->create())
            ->get('/dashboard')->assertRedirect(route('barber.dashboard'));
    }

    public function test_registration_ignores_role_field(): void
    {
        $this->post('/registreren', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'geheim-wachtwoord',
            'role' => 'barber',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(UserRole::Customer, User::first()->role);
        $this->assertAuthenticated();
    }

    public function test_registration_works_without_phone(): void
    {
        $this->post('/registreren', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'geheim-wachtwoord',
        ]);

        $this->assertNull(User::first()->phone);
    }
}
