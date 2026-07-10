<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Settings</h1>
            <p class="text-sm text-slate-500">Manage your API key and account security.</p>
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
                @if (auth()->user()->password)
                    <livewire:profile.update-password-form />
                @else
                    <h2 class="text-lg font-medium text-gray-900">Password</h2>
                    <div class="mt-3 flex items-center gap-3 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3">
                        <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.99.66-2.26 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.18 7.06l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z"/></svg>
                        <p class="text-sm text-slate-600">You're signed in with <strong>Google</strong>, so no password is needed. Your login security is handled by Google.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-white shadow-sm border border-slate-200 rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
