<?php

namespace Tests\Feature;

use App\Models\Barbershop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_without_settings_row_gets_defaults(): void
    {
        $shop = Barbershop::factory()->create();

        $this->assertSame(30, $shop->settings->booking_interval);
        $this->assertSame(30, $shop->settings->max_booking_days);
        $this->assertSame(60, $shop->settings->cancellation_limit);
        $this->assertSame(60, $shop->settings->reschedule_limit);
    }

    public function test_settings_can_differ_per_shop(): void
    {
        $jf = Barbershop::factory()->create(['name' => 'JF Cuts']);
        $fresh = Barbershop::factory()->create(['name' => 'Fresh Cuts']);

        $jf->settings()->create(['max_booking_days' => 30]);
        $fresh->settings()->create(['max_booking_days' => 14, 'booking_interval' => 15]);

        $this->assertSame(30, $jf->fresh()->settings->max_booking_days);
        $this->assertSame(14, $fresh->fresh()->settings->max_booking_days);
        $this->assertSame(15, $fresh->fresh()->settings->booking_interval);
    }

    public function test_a_shop_can_only_have_one_settings_row(): void
    {
        $shop = Barbershop::factory()->create();
        $shop->settings()->create([]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $shop->settings()->create([]);
    }
}
