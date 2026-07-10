<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';
    public string $confirm = '';

    /**
     * Delete the currently authenticated user.
     * Password-based accounts confirm with their password;
     * Google-connected accounts (no password) confirm by typing DELETE.
     */
    public function deleteUser(Logout $logout): void
    {
        $user = Auth::user();

        if ($user->password) {
            $this->validate(['password' => ['required', 'string', 'current_password']]);
        } else {
            $this->validate(
                ['confirm' => ['required', 'in:DELETE']],
                ['confirm.in' => 'I-type ang salitang DELETE para kumpirmahin.'],
            );
        }

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Delete Account') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Kapag na-delete ang account mo, permanenteng mabubura ang lahat ng data nito. I-download muna ang gusto mong itago.') }}
        </p>
    </header>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('Delete Account') }}
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Sigurado ka bang i-delete ang account mo?') }}</h2>

            @if (auth()->user()->password)
                <p class="mt-1 text-sm text-gray-600">{{ __('Ilagay ang password para kumpirmahin ang pagbura ng account.') }}</p>
                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />
                    <x-text-input wire:model="password" id="password" name="password" type="password"
                                  class="mt-1 block w-3/4" placeholder="{{ __('Password') }}" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            @else
                <p class="mt-1 text-sm text-gray-600">{{ __('Naka-connect ka via Google. I-type ang salitang') }} <strong>DELETE</strong> {{ __('para kumpirmahin.') }}</p>
                <div class="mt-6">
                    <x-input-label for="confirm" value="DELETE" class="sr-only" />
                    <x-text-input wire:model="confirm" id="confirm" name="confirm" type="text"
                                  class="mt-1 block w-3/4" placeholder="DELETE" />
                    <x-input-error :messages="$errors->get('confirm')" class="mt-2" />
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button>
                <x-danger-button class="ms-3">{{ __('Delete Account') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
