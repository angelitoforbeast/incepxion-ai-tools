<?php

namespace App\Livewire\Admin;

use App\Models\Tool;
use App\Services\AdCopyService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PromptManager extends Component
{
    public string $systemPrompt = '';
    public string $model = 'gpt-4o';

    public function mount(): void
    {
        $tool = Tool::where('slug', 'ad-copy-generator')->firstOrFail();
        $config = $tool->config ?? [];
        $this->systemPrompt = $config['system_prompt'] ?? AdCopyService::DEFAULT_SYSTEM;
        $this->model = $config['default_model'] ?? 'gpt-4o';
    }

    public function save(): void
    {
        $this->validate([
            'systemPrompt' => ['required', 'string', 'min:20'],
            'model'        => ['required', 'string', 'max:60'],
        ]);

        $tool = Tool::where('slug', 'ad-copy-generator')->firstOrFail();
        $config = $tool->config ?? [];
        $config['system_prompt'] = trim($this->systemPrompt);
        $config['default_model'] = trim($this->model);
        $tool->update(['config' => $config]);

        session()->flash('msg', 'Prompt saved. It will apply to the next generation.');
    }

    public function resetDefault(): void
    {
        $this->systemPrompt = AdCopyService::DEFAULT_SYSTEM;
        session()->flash('msg', 'Reset to the default prompt — click Save to apply.');
    }

    public function render()
    {
        return view('livewire.admin.prompt-manager');
    }
}
