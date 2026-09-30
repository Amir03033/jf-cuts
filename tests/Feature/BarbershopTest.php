<?php

namespace Tests\Feature;

use App\Models\Barbershop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarbershopTest extends TestCase
{
    use RefreshDatabase;

    public function test_barbershop_belongs_to_its_owner(): void
    {
        $barber = User::factory()->barber()->create();
        $shop = $barber->barbershops()->create(['name' => 'JF Cuts']);

        $this->assertTrue($shop->owner->is($barber));
        $this->assertSame('JF Cuts', $barber->barbershops()->first()->name);
    }

    public function test_owner_id_cannot_be_mass_assigned(): void
    {
        $other = User::factory()->barber()->create();
        $barber = User::factory()->barber()->create();

        $shop = $barber->barbershops()->create([
            'name' => 'JF Cuts',
            'owner_id' => $other->id,   // wordt genegeerd
        ]);

        $this->assertSame($barber->id, $shop->owner_id);
    }

    public function test_only_the_owner_can_update_the_shop(): void
    {
        $shop = Barbershop::factory()->create();
        $otherBarber = User::factory()->barber()->create();
        $customer = User::factory()->create();

        $this->assertTrue($shop->owner->can('update', $shop));
        $this->assertFalse($otherBarber->can('update', $shop));
        $this->assertFalse($customer->can('update', $shop));
    }

    public function test_multiple_barbershops_can_exist(): void
    {
        Barbershop::factory()->create(['name' => 'JF Cuts']);
        Barbershop::factory()->create(['name' => 'Fresh Cuts']);

        $this->assertDatabaseCount('barbershops', 2);
    }
}
