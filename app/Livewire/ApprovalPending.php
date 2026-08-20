<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pending Approval')]
class ApprovalPending extends Component
{
    public function mount()
    {
        return $this->routeByStatus();
    }

    /** Polled from the view — moves the user on the moment their status changes. */
    public function refreshStatus()
    {
        return $this->routeByStatus();
    }

    protected function routeByStatus()
    {
        $status = auth()->user()->fresh()->status;

        if ($status === 'approved') {
            return $this->redirect(route('dashboard'), navigate: true);
        }

        if (in_array($status, ['rejected', 'suspended'], true)) {
            return $this->redirect(route('account.rejected'), navigate: true);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.approval-pending');
    }
}
