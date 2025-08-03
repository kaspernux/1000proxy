<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\Order;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Component Showcase - 1000 PROXIES')]
class ComponentShowcase extends Component
{
    use LivewireAlert;

    public $activeDemo = 'server-browser';
    public bool $isLoading = false;

    protected function rules()
    {
        return [
            'activeDemo' => 'required|string|in:server-browser,client-monitor,order-tracker,progress-bar,payment-processor,proxy-config,animated-toggle,dropdown',
        ];
    }

    public function mount()
    {
        try {
            // Security logging for showcase access
            Log::info('Component showcase accessed', [
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

        } catch (\Exception $e) {
            Log::error('Component showcase mount error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
        }
    }

    public function switchDemo($demo)
    {
        try {
            // Rate limiting for demo switching
            $key = 'switch_demo.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 30)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw new \Exception("Too many demo switches. Please try again in {$seconds} seconds.");
            }

            $this->validate(['activeDemo' => $demo], [
                'activeDemo' => $demo
            ]);

            RateLimiter::hit($key, 60); // 1-minute window

            $this->activeDemo = $demo;

            // Security logging
            Log::info('Demo switched in showcase', [
                'demo' => $demo,
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
            ]);

        } catch (\Exception $e) {
            Log::error('Demo switch error', [
                'demo' => $demo ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to switch demo. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function render()
    {
        try {
            $this->isLoading = true;

            // Sample data for demonstrations
            $sampleServers = Server::with(['serverPlans', 'serverPlans.category', 'serverPlans.brand'])
                ->where('is_active', true)
                ->take(6)
                ->get();

            $sampleClient = ServerClient::with(['server', 'serverPlan'])->first();
            $sampleOrder = Order::with(['user'])->first();

            return view('livewire.component-showcase', [
                'sampleServers' => $sampleServers,
                'sampleClient' => $sampleClient,
                'sampleOrder' => $sampleOrder
            ]);

        } catch (\Exception $e) {
            Log::error('Component showcase render error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to load showcase. Please refresh the page.', [
                'position' => 'center',
                'timer' => 4000,
                'toast' => false,
            ]);

            return view('livewire.component-showcase', [
                'sampleServers' => collect(),
                'sampleClient' => null,
                'sampleOrder' => null
            ]);
        } finally {
            $this->isLoading = false;
        }
    }
}
