<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Page name for the browser tab. Livewire full-page components set this themselves via
     * #[Title]; plain views pass it in (<x-app-layout title="...">) so every tab is
     * distinguishable rather than all reading the same app name.
     */
    public function __construct(public ?string $title = null)
    {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
