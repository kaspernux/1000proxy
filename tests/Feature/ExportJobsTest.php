<?php

namespace Tests\Feature;

use App\Jobs\ExportOrdersJob;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportJobsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function export_orders_job_creates_file()
    {
        Storage::fake('local');
    Customer::factory()->create();
    Order::factory()->count(2)->create(['payment_status' => 'paid']);

    dispatch_sync(new ExportOrdersJob());

    $files = Storage::disk('local')->files('exports/orders');
    $this->assertNotEmpty($files, 'No export file generated');
    $this->assertStringEndsWith('.csv', $files[0]);
    }
}
