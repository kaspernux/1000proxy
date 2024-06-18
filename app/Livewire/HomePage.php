<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\ServerBrand;
use App\Models\ServerCategory;


#[Title('Home Page - 1000 PROXIES')]

class HomePage extends Component
{
    public function render()
    {
        $brands = ServerBrand::where('is_active', 1)->get();
        $categories = ServerCategory::where('is_active', true)->get();


        return view('livewire.home-page', [
            'brands' => $brands,
            'categories' => $categories,
        ]);

   }
}