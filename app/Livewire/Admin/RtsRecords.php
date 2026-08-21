<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin · RTS Records')]
class RtsRecords extends Component
{
    use WithPagination;

    /** Columns that may be sorted on — anything else is ignored. */
    private const SORTABLE = ['submission_time', 'waybill_number', 'item_name', 'cod', 'status', 'user_id'];

    #[Url(as: 'u', except: '')]
    public string $userId = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 's', except: '')]
    public string $status = '';

    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    public string $sortBy = 'submission_time';
    public string $sortDir = 'desc';
    public int $perPage = 50;

    public function updated($property): void
    {
        // Any filter change invalidates the current page.
        if ($property !== 'sortBy' && $property !== 'sortDir') {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('userId', 'search', 'status', 'from', 'to');
        $this->resetPage();
    }

    public function getActiveFiltersProperty(): int
    {
        return (int) ($this->userId !== '')
            + (int) ($this->search !== '')
            + (int) ($this->status !== '')
            + (int) ($this->from !== '')
            + (int) ($this->to !== '');
    }

    private function query()
    {
        // Only the displayed columns. from_jnts carries a TEXT column (remarks), and a
        // "select *" with an ORDER BY makes MySQL pack whole rows into the sort buffer —
        // which is what blew up the generations listing with "Out of sort memory".
        return DB::table('from_jnts')
            ->select('id', 'user_id', 'waybill_number', 'submission_time', 'item_name', 'cod', 'status')
            ->when($this->userId !== '', fn ($q) => $q->where('user_id', (int) $this->userId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->from !== '', fn ($q) => $q->where('submission_time', '>=', $this->from.' 00:00:00'))
            ->when($this->to !== '', fn ($q) => $q->where('submission_time', '<=', $this->to.' 23:59:59'))
            ->when($this->search !== '', function ($q) {
                $term = trim($this->search);
                // Waybills match from the start so the index can be used; item names are
                // matched anywhere, which is fine given how few distinct ones there are.
                $q->where(function ($w) use ($term) {
                    $w->where('waybill_number', 'like', $term.'%')
                        ->orWhere('item_name', 'like', '%'.$term.'%');
                });
            });
    }

    public function render()
    {
        $rows = $this->query()
            ->orderBy($this->sortBy, $this->sortDir === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.rts-records', [
            'activeTab' => 'admin.rts-records',
            'rows'      => $rows,
            'users'     => User::whereIn('id', DB::table('from_jnts')->distinct()->pluck('user_id'))
                ->orderBy('name')->get(['id', 'name', 'email']),
            'statuses'  => DB::table('from_jnts')->distinct()->orderBy('status')->pluck('status'),
        ]);
    }
}
