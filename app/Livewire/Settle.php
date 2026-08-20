<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Settle Account')]
class Settle extends Component
{
    public static function defaults(): array
    {
        return [
            'message' => "Your access has expired. To continue using Incepxion AI, please settle your subscription. Once paid, message us and we'll reactivate your account.",
            'gcash'   => '',
            'bank'    => '',
            'contact' => '',
        ];
    }

    public function render()
    {
        $info = array_merge(self::defaults(), (array) Setting::get('settle', []));

        return view('livewire.settle', [
            'info' => $info,
            'user' => auth()->user(),
        ]);
    }
}
