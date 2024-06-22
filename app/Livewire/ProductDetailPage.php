<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerCategory;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use Livewire\WithPagination;

#[Title('Product Detail - 1000 PROXIES')]

class ProductDetailPage extends Component
{
    public $slug;

    public function mount($slug){
        $this->slug = $slug;
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            'serverPlan' => ServerPlan::where('slug', $this->slug)->firstOrFail(),
        ]);

   }
}