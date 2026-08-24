<?php

namespace App\Livewire\Admin;

use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin · Data Logs')]
class GenerationLog extends Component
{
    use WithPagination;

    public ?int $expanded = null;

    #[Url(as: 'u', except: '')]
    public string $userId = '';

    #[Url(as: 's', except: '')]
    public string $status = '';

    #[Url(as: 'm', except: '')]
    public string $model = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    public int $perPage = 20;

    public function updated($property): void
    {
        if ($property !== 'expanded') {
            $this->resetPage();
        }
    }

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function clearFilters(): void
    {
        $this->reset('userId', 'status', 'model', 'search', 'from', 'to');
        $this->resetPage();
    }

    public function getActiveFiltersProperty(): int
    {
        return (int) ($this->userId !== '') + (int) ($this->status !== '')
            + (int) ($this->model !== '') + (int) ($this->search !== '')
            + (int) ($this->from !== '') + (int) ($this->to !== '');
    }

    public function render()
    {
        $logs = Generation::with(['user', 'tool'])
            ->when($this->userId !== '', fn ($q) => $q->where('user_id', (int) $this->userId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->model !== '', fn ($q) => $q->where('model', $this->model))
            ->when($this->from !== '', fn ($q) => $q->where('created_at', '>=', $this->from.' 00:00:00'))
            ->when($this->to !== '', fn ($q) => $q->where('created_at', '<=', $this->to.' 23:59:59'))
            // The product name lives inside the stored input JSON, so it is matched there.
            ->when($this->search !== '', fn ($q) => $q->where('input->product_name', 'like', '%'.trim($this->search).'%'))
            // Order by the primary key, not created_at: id is auto-increment so this is the
            // same newest-first order, but it uses the PK index instead of a filesort. A
            // filesort on these rows tries to pack the large generated-content column into
            // the sort buffer and blows it (SQLSTATE HY001, "Out of sort memory").
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.admin.generation-log', [
            'activeTab' => 'admin.logs',
            'logs'      => $logs,
            'users'     => User::whereIn('id', DB::table('generations')->whereNotNull('user_id')->distinct()->pluck('user_id'))
                ->orderBy('name')->get(['id', 'name', 'email']),
            'models'    => DB::table('generations')->whereNotNull('model')->where('model', '<>', '')
                ->distinct()->orderBy('model')->pluck('model'),
        ]);
    }
}
