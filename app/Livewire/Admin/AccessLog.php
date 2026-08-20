<?php

namespace App\Livewire\Admin;

use App\Models\AccessLog as AccessLogModel;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin · Access Log')]
class AccessLog extends Component
{
    use WithPagination;

    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';
    public ?int $userId = null;
    public string $type = '';

    public function sort(string $col): void
    {
        if (! in_array($col, ['created_at', 'user_id', 'type', 'ip_address'], true)) {
            return;
        }
        if ($this->sortBy === $col) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $col;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('userId', 'type');
        $this->resetPage();
    }

    public function updatedUserId(): void { $this->resetPage(); }
    public function updatedType(): void { $this->resetPage(); }

    public function render()
    {
        $rows = AccessLogModel::with('user')
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->orderBy($this->sortBy, $this->sortDir === 'asc' ? 'asc' : 'desc')
            ->paginate(30);

        $users = User::whereIn('id', AccessLogModel::select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')->get(['id', 'name', 'email']);

        return view('livewire.admin.access-log', [
            'activeTab'     => 'admin.access',
            'rows'          => $rows,
            'users'         => $users,
            'activeFilters' => ($this->userId ? 1 : 0) + ($this->type ? 1 : 0),
        ]);
    }
}
