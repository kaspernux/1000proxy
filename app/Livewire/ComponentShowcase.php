<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\Order;
use Livewire\Attributes\Title;

#[Title('Component Showcase - 1000 PROXIES')]
class ComponentShowcase extends Component
{
    public $activeDemo = 'server-browser';

    public function render()
    {
        // Sample data for demonstrations
        $sampleServers = Server::with(['serverPlans', 'serverPlans.serverCategory', 'serverPlans.serverBrand'])
            ->take(6)
            ->get();

        $sampleClient = ServerClient::with(['server', 'serverPlan'])->first();
        $sampleOrder = Order::with(['user'])->first();

        return view('livewire.component-showcase', [
            'sampleServers' => $sampleServers,
            'sampleClient' => $sampleClient,
            'sampleOrder' => $sampleOrder
        ]);
    }

    public function switchDemo($demo)
    {
        $this->activeDemo = $demo;
    }
}
