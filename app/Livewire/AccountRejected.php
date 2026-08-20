<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Account Status')]
class AccountRejected extends Component
{
    public function mount()
    {
        return $this->routeByStatus();
    }

    /** Polled — if an admin later approves/restores the account, move the user on. */
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

        if ($status === 'pending') {
            return $this->redirect(route('approval.pending'), navigate: true);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.account-rejected', ['user' => auth()->user()]);
    }
}
