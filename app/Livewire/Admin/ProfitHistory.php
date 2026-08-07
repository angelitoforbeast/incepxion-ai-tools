<?php

namespace App\Livewire\Admin;

use App\Models\ProfitCalculation;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProfitHistory extends Component
{
    use WithPagination;

    public ?int $userId = null;

    public function updatedUserId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $rows = ProfitCalculation::with('user')
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->latest()
            ->paginate(25);

        // Only users who actually have calculations, for the dropdown.
        $users = User::whereIn('id', ProfitCalculation::select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.admin.profit-history', [
            'activeTab' => 'admin.profit',
            'rows'      => $rows,
            'users'     => $users,
        ]);
    }
}
