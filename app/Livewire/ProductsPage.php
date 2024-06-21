<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\ServerPlan;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use Livewire\WithPagination;


#[Title('Products Page - 1000 PROXIES')]

class ProductsPage extends Component
{
    use withPagination;

    public function render()
    {
        $serverQuery = ServerPlan::query()->where('is_active', 1);

        return view('livewire.products-page', [
            'serverPlans' => $serverQuery->paginate(12),
        ]);

   }
}
