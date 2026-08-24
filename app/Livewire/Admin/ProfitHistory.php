<?php

namespace App\Livewire\Admin;

use App\Models\ProfitCalculation;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin · Profit Log')]
class ProfitHistory extends Component
{
    use WithPagination;

    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';

    public string $type = '';           // '' | net | adjustment
    public array $selectedUsers = [];
    public array $min = [];
    public array $max = [];

    #[\Livewire\Attributes\Url(as: 'from', except: '')]
    public string $from = '';

    #[\Livewire\Attributes\Url(as: 'to', except: '')]
    public string $to = '';

    public int $perPage = 25;

    private const NUMERIC = [
        'cpp', 'cogs', 'shipping_fee', 'orders', 'cod_price', 'cod_fee', 'rts',
        'net_profit', 'target_net_profit', 'suggested_rts', 'suggested_cpp',
    ];

    private function sortable(): array
    {
        return array_merge(['created_at', 'user_id', 'type'], self::NUMERIC);
    }

    public function sort(string $col): void
    {
        if (! in_array($col, $this->sortable(), true)) {
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
        $this->reset('selectedUsers', 'min', 'max', 'type', 'from', 'to');
        $this->resetPage();
    }

    public function updatedType(): void { $this->resetPage(); }
    public function updatedSelectedUsers(): void { $this->resetPage(); }
    public function updatedMin(): void { $this->resetPage(); }
    public function updatedMax(): void { $this->resetPage(); }
    public function updatedFrom(): void { $this->resetPage(); }
    public function updatedTo(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    public function render()
    {
        $q = ProfitCalculation::with('user')
            ->when($this->type, fn ($x) => $x->where('type', $this->type))
            ->when($this->selectedUsers, fn ($x) => $x->whereIn('user_id', $this->selectedUsers))
            ->when($this->from !== '', fn ($x) => $x->where('created_at', '>=', $this->from.' 00:00:00'))
            ->when($this->to !== '', fn ($x) => $x->where('created_at', '<=', $this->to.' 23:59:59'));

        foreach (self::NUMERIC as $col) {
            if (is_numeric($this->min[$col] ?? null)) {
                $q->where($col, '>=', $this->min[$col]);
            }
            if (is_numeric($this->max[$col] ?? null)) {
                $q->where($col, '<=', $this->max[$col]);
            }
        }

        $sortBy = in_array($this->sortBy, $this->sortable(), true) ? $this->sortBy : 'created_at';
        $q->orderBy($sortBy, $this->sortDir === 'asc' ? 'asc' : 'desc');

        $users = User::whereIn('id', ProfitCalculation::select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')->get(['id', 'name', 'email']);

        return view('livewire.admin.profit-history', [
            'activeTab'     => 'admin.profit',
            'rows'          => $q->paginate($this->perPage),
            'users'         => $users,
            'activeFilters' => count($this->selectedUsers) + ($this->type ? 1 : 0)
                + ($this->from !== '' ? 1 : 0) + ($this->to !== '' ? 1 : 0)
                + count(array_filter($this->min, fn ($v) => is_numeric($v)))
                + count(array_filter($this->max, fn ($v) => is_numeric($v))),
        ]);
    }
}
