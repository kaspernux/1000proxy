<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ServerCategory;


#[Title('Category Page - 1000 PROXIES')]

class CategoriesPage extends Component
{
    public function render()
    {
        $categories = ServerCategory::where('is_active', true)->get();
        return view('livewire.categories-page', [
            'categories' => $categories
        ]);
    }
}