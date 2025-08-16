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
        // Configure exports to use a dedicated in-memory fake disk
        config(['exports.disk' => 'exports_testing']);
        config(['exports.path' => 'orders']);
        config(['filesystems.disks.exports_testing' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/exports'),
        ]]);
        Storage::fake('exports_testing');
    Customer::factory()->create();
    Order::factory()->count(2)->create(['payment_status' => 'paid']);

    dispatch_sync(new ExportOrdersJob());

    // Scan for files
    $files = Storage::disk('exports_testing')->files('orders');
    $this->assertNotEmpty($files, 'No export file generated');
    $this->assertStringEndsWith('.csv', $files[0]);
    }
}
