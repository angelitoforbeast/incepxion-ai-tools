<?php

namespace App\Livewire\Admin;

use App\Models\ProfitCalculation;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProfitHistory extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.admin.profit-history', [
            'activeTab' => 'admin.profit',
            'rows'      => ProfitCalculation::with('user')->latest()->paginate(25),
        ]);
    }
}
