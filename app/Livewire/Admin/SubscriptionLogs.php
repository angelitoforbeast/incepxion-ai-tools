<?php

namespace App\Livewire\Admin;

use App\Models\SubscriptionLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SubscriptionLogs extends Component
{
    use WithPagination;

    public ?int $userId = null;
    public string $action = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function clearFilters(): void
    {
        $this->reset('userId', 'action', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function updatedUserId(): void { $this->resetPage(); }
    public function updatedAction(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function render()
    {
        $rows = SubscriptionLog::with(['user', 'admin'])
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->action, fn ($q) => $q->where('action', $this->action))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(30);

        $users = User::whereIn('id', SubscriptionLog::select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')->get(['id', 'name', 'email']);

        $activeFilters = ($this->userId ? 1 : 0) + ($this->action ? 1 : 0)
            + ($this->dateFrom ? 1 : 0) + ($this->dateTo ? 1 : 0);

        return view('livewire.admin.subscription-logs', [
            'activeTab'     => 'admin.subscriptions',
            'rows'          => $rows,
            'users'         => $users,
            'activeFilters' => $activeFilters,
        ]);
    }
}
