<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Settings</h1>
            <p class="text-sm text-slate-500">I-manage ang API key at account security mo.</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="p-6 sm:p-8 bg-white shadow-sm border border-slate-200 rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.api-key-form />
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-white shadow-sm border border-slate-200 rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-white shadow-sm border border-slate-200 rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
