<?php

namespace App\Livewire\Admin;

use App\Models\PromptVersion;
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
        $tool = $this->tool();
        $config = $tool->config ?? [];
        $this->systemPrompt = $config['system_prompt'] ?? AdCopyService::DEFAULT_SYSTEM;
        $this->model = $config['default_model'] ?? 'gpt-4o';
    }

    private function tool(): Tool
    {
        return Tool::where('slug', 'ad-copy-generator')->firstOrFail();
    }

    public function save(): void
    {
        $this->validate([
            'systemPrompt' => ['required', 'string', 'min:20'],
            'model'        => ['required', 'string', 'max:60'],
        ]);

        $tool = $this->tool();
        $config = $tool->config ?? [];
        $config['system_prompt'] = trim($this->systemPrompt);
        $config['default_model'] = trim($this->model);
        $tool->update(['config' => $config]);

        // Snapshot this version into history.
        PromptVersion::create([
            'tool_id'       => $tool->id,
            'system_prompt' => trim($this->systemPrompt),
            'model'         => trim($this->model),
            'created_by'    => auth()->id(),
        ]);

        session()->flash('msg', 'Prompt saved. A new version was added to history.');
    }

    /** Load a past version into the editor (review, then Save to apply). */
    public function restore(int $versionId): void
    {
        $version = PromptVersion::where('tool_id', $this->tool()->id)->findOrFail($versionId);
        $this->systemPrompt = $version->system_prompt;
        $this->model = $version->model ?? 'gpt-4o';
        session()->flash('msg', 'Version loaded into the editor — click Save to apply it.');
    }

    public function resetDefault(): void
    {
        $this->systemPrompt = AdCopyService::DEFAULT_SYSTEM;
        session()->flash('msg', 'Reset to the default prompt — click Save to apply.');
    }

    public function render()
    {
        return view('livewire.admin.prompt-manager', [
            'versions' => PromptVersion::where('tool_id', $this->tool()->id)
                ->with('author')
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }
}
