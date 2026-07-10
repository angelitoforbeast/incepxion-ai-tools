<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Account Pending Approval') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                <div class="text-5xl mb-4">⏳</div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Salamat sa pag-sign up, {{ auth()->user()->name }}!
                </h3>
                <p class="text-gray-600 mb-6">
                    Naka-<strong>pending approval</strong> pa ang account mo. Susuriin ito ng admin —
                    makakatanggap ka ng access sa mga AI tools kapag na-approve na.
                </p>

                <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4 text-left text-sm text-gray-700 mb-6">
                    💡 Habang naghihintay, pwede mo nang i-set up ang iyong
                    <strong>OpenAI API key</strong> sa
                    <a href="{{ route('settings') }}" class="text-indigo-600 underline">Settings</a>
                    para handa ka na agad pag-approved.
                </div>

                <div class="flex items-center justify-center gap-3">
                    <a href="{{ route('settings') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                        I-set up ang API Key
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-md hover:bg-gray-300">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
