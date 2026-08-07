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

    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';

    public array $selectedUsers = [];
    public array $min = [];
    public array $max = [];

    private const NUMERIC = ['cpp', 'cogs', 'shipping_fee', 'orders', 'cod_price', 'cod_fee', 'rts', 'net_profit'];

    public function sort(string $col): void
    {
        $sortable = array_merge(['created_at', 'user_id'], self::NUMERIC);
        if (! in_array($col, $sortable, true)) {
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
        $this->reset('selectedUsers', 'min', 'max');
        $this->resetPage();
    }

    public function updatedSelectedUsers(): void { $this->resetPage(); }
    public function updatedMin(): void { $this->resetPage(); }
    public function updatedMax(): void { $this->resetPage(); }

    public function render()
    {
        $q = ProfitCalculation::with('user')
            ->when($this->selectedUsers, fn ($x) => $x->whereIn('user_id', $this->selectedUsers));

        foreach (self::NUMERIC as $col) {
            if (is_numeric($this->min[$col] ?? null)) {
                $q->where($col, '>=', $this->min[$col]);
            }
            if (is_numeric($this->max[$col] ?? null)) {
                $q->where($col, '<=', $this->max[$col]);
            }
        }

        $sortBy = in_array($this->sortBy, array_merge(['created_at', 'user_id'], self::NUMERIC), true) ? $this->sortBy : 'created_at';
        $q->orderBy($sortBy, $this->sortDir === 'asc' ? 'asc' : 'desc');

        $users = User::whereIn('id', ProfitCalculation::select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')->get(['id', 'name', 'email']);

        return view('livewire.admin.profit-history', [
            'activeTab'     => 'admin.profit',
            'rows'          => $q->paginate(25),
            'users'         => $users,
            'activeFilters' => count($this->selectedUsers)
                + count(array_filter($this->min, fn ($v) => is_numeric($v)))
                + count(array_filter($this->max, fn ($v) => is_numeric($v))),
        ]);
    }
}
