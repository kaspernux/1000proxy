<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ServerCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;

#[Title('Category Page - 1000 PROXIES')]
class CategoriesPage extends Component
{
    use LivewireAlert;

    public bool $isLoading = false;
    public array $selectedCategories = [];

    protected function rules()
    {
        return [
            'selectedCategories' => 'array',
            'selectedCategories.*' => 'integer|exists:server_categories,id',
        ];
    }

    public function mount()
    {
        try {
            // Security logging for category page access
            Log::info('Categories page accessed', [
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

        } catch (\Exception $e) {
            Log::error('Categories page mount error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
        }
    }

    public function viewCategory($categoryId)
    {
        try {
            // Rate limiting for category navigation
            $key = 'view_category.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 30)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw new \Exception("Too many category views. Please try again in {$seconds} seconds.");
            }

            $category = ServerCategory::where('id', $categoryId)
                                    ->where('is_active', true)
                                    ->firstOrFail();

            RateLimiter::hit($key, 60); // 1-minute window

            // Security logging
            Log::info('Category viewed', [
                'category_id' => $categoryId,
                'category_name' => $category->name,
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
            ]);

            return redirect()->route('products', ['category' => $category->slug]);

        } catch (\Exception $e) {
            Log::error('Category view error', [
                'category_id' => $categoryId ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to load category. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function render()
    {
        try {
            $categories = ServerCategory::where('is_active', true)
                                      ->with(['plans' => function($query) {
                                          $query->where('is_active', true);
                                      }, 'servers' => function($query) {
                                          $query->where('is_active', true);
                                      }])
                                      ->orderBy('name', 'asc')
                                      ->get();

            return view('livewire.categories-page', [
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Categories page render error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to load categories. Please refresh the page.', [
                'position' => 'center',
                'timer' => 4000,
                'toast' => false,
            ]);

            return view('livewire.categories-page', [
                'categories' => collect()
            ]);
        }
    }
}