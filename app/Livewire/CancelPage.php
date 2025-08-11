<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Livewire\Traits\LivewireAlertV4;
use Livewire\Attributes\Title;

#[Title('Cancel Order - 1000 PROXIES')]
class CancelPage extends Component
{
    use LivewireAlertV4;

    public bool $isLoading = false;
    public string $reason = '';
    public bool $showFeedbackForm = false;

    protected function rules()
    {
        return [
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function mount()
    {
        try {
            // Security logging for cancel page access
            Log::info('Cancel page accessed', [
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer')
            ]);

        } catch (\Exception $e) {
            Log::error('Cancel page mount error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
        }
    }

    public function submitFeedback()
    {
        try {
            $this->isLoading = true;

            // Rate limiting for feedback submissions
            $key = 'cancel_feedback.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw new \Exception("Too many feedback submissions. Please try again in {$seconds} seconds.");
            }

            $this->validate();

            if (!empty($this->reason)) {
                RateLimiter::hit($key, 300); // 5-minute window

                // Store feedback or send to admin
                Log::info('Order cancellation feedback received', [
                    'reason' => $this->reason,
                    'customer_id' => Auth::guard('customer')->id(),
                    'ip' => request()->ip(),
                ]);

                $this->alert('success', 'Thank you for your feedback. We appreciate your input.', [
                    'position' => 'center',
                    'timer' => 4000,
                    'toast' => false,
                ]);

                $this->reset(['reason', 'showFeedbackForm']);
            }

        } catch (\Exception $e) {
            Log::error('Cancel feedback submission error', [
                'error' => $e->getMessage(),
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to submit feedback. Please try again.', [
                'position' => 'center',
                'timer' => 4000,
                'toast' => false,
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function toggleFeedbackForm()
    {
        $this->showFeedbackForm = !$this->showFeedbackForm;
        if ($this->showFeedbackForm) {
            $this->reason = '';
        }
    }

    public function returnToCart()
    {
        try {
            // Security logging
            Log::info('Customer returned to cart from cancel page', [
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
            ]);

            return redirect()->route('cart');

        } catch (\Exception $e) {
            Log::error('Return to cart error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to redirect. Please try again.', [
                'position' => 'center',
                'timer' => 3000,
                'toast' => false,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.cancel-page');
    }
}