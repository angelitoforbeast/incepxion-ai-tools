<?php

namespace App\Livewire\Admin;

use App\Models\ErrorLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin · Error Logs')]
class ErrorLogs extends Component
{
    use WithPagination;

    public ?int $expandedId = null;

    public function toggle(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function clearLogs(): void
    {
        ErrorLog::query()->delete();
        $this->resetPage();
        $this->dispatch('notify', message: 'Error logs cleared.', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.error-logs', [
            'activeTab' => 'admin.errors',
            'rows'      => ErrorLog::with('user')->latest('id')->paginate(20),
            'total'     => ErrorLog::count(),
        ]);
    }
}
