<?php

namespace App\Livewire\Admin;

use App\Livewire\Settle;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin · Billing')]
class BillingSettings extends Component
{
    public string $message = '';
    public string $gcash = '';
    public string $bank = '';
    public string $contact = '';

    public function mount(): void
    {
        $info = array_merge(Settle::defaults(), (array) Setting::get('settle', []));
        $this->message = (string) $info['message'];
        $this->gcash = (string) $info['gcash'];
        $this->bank = (string) $info['bank'];
        $this->contact = (string) $info['contact'];
    }

    public function save(): void
    {
        $this->validate([
            'message' => ['required', 'string', 'max:2000'],
            'gcash'   => ['nullable', 'string', 'max:1000'],
            'bank'    => ['nullable', 'string', 'max:1000'],
            'contact' => ['nullable', 'string', 'max:1000'],
        ]);

        Setting::put('settle', [
            'message' => trim($this->message),
            'gcash'   => trim($this->gcash),
            'bank'    => trim($this->bank),
            'contact' => trim($this->contact),
        ]);

        session()->flash('msg', 'Settle / billing details saved.');
    }

    public function render()
    {
        return view('livewire.admin.billing-settings', [
            'activeTab' => 'admin.billing',
        ]);
    }
}
