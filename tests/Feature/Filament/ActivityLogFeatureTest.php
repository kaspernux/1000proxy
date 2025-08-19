<?php

namespace Tests\Feature\Filament;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Livewire\Livewire;

class ActivityLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_view_activity_logs()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ActivityLog::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertSee('Activity Logs');
    }

    #[Test]
    public function non_admin_cannot_view_activity_logs()
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->get('/admin/activity-logs')
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_export_activity_logs_csv()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $logs = ActivityLog::factory()->count(5)->create();

        $this->actingAs($admin);

        Livewire::test(\App\Filament\Admin\Resources\ActivityLogResource\Pages\ListActivityLogs::class)
            ->callTableBulkAction('export_csv', $logs->pluck('id')->toArray())
            ->assertOk();
    }
}
