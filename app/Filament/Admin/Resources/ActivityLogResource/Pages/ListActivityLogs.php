<?php

namespace App\Filament\Admin\Resources\ActivityLogResource\Pages;

use App\Filament\Admin\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    /**
     * Ensure non-admin users receive a 403 (not a redirect) before Filament's
     * internal authorization flow potentially issues a redirect.
     */
    protected function authorizeAccess(): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        parent::authorizeAccess();
    }

    public function mount(): void
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403);
        }
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('home')
                ->label('Home')
                ->icon('heroicon-o-home')
                ->url(fn () => url('/'))
                ->color('gray'),
            \Filament\Actions\Action::make('export_recent')
                ->label('Export Recent CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $records = ActivityLog::query()->latest('id')->limit(1000)->get();
                    $filename = 'activity-logs-recent-' . now()->format('Ymd-His') . '.csv';
                    return response()->streamDownload(function () use ($records) {
                        $out = fopen('php://output', 'w');
                        fputcsv($out, ['When', 'User', 'Action', 'Subject', 'Subject ID', 'IP Address']);
                        foreach ($records as $log) {
                            fputcsv($out, [
                                optional($log->created_at)?->toDateTimeString(),
                                optional($log->user)?->name,
                                $log->action,
                                class_basename($log->subject_type),
                                $log->subject_id,
                                $log->ip_address,
                            ]);
                        }
                        fclose($out);
                    }, $filename, ['Content-Type' => 'text/csv']);
                }),
            \Filament\Actions\Action::make('help')
                ->label('Help')
                ->icon('heroicon-o-question-mark-circle')
                ->modalHeading('About Activity Logs')
                ->modalContent(new \Illuminate\Support\HtmlString('Activity logs capture key actions performed by staff and system automations. Use filters to narrow by date, action, or subject. Export selections using bulk actions or export recent entries from here.'))
                ->modalSubmitAction(false)
                ->color('gray'),
        ];
    }
}
