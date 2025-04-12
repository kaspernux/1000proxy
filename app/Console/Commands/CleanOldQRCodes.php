<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;


class CleanOldQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-old-q-r-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete QR code images older than 7 days';
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dir = storage_path('app/public/qr_codes');
        $files = File::files($dir);

        foreach ($files as $file) {
            if (now()->diffInDays($file->getMTime()) > 7) {
                File::delete($file->getRealPath());
                $this->info("Deleted: {$file->getFilename()}");
            }
        }

        $this->info('Old QR codes cleaned.');
    }
}
