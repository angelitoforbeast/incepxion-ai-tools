@php
    // Escape the admin-entered text, then turn any http(s) URL into a clickable link.
    $linkify = function (?string $text) {
        $escaped = e((string) $text);
        return preg_replace(
            '~(https?://[^\s]+)~i',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-700 underline break-all">$1</a>',
            $escaped
        );
    };
@endphp
<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="bg-amber-50 border-b border-amber-200 px-6 py-5 text-center">
            <div class="text-4xl mb-1">⏳</div>
            <h1 class="text-xl font-bold text-amber-900">Settle your account</h1>
            @if ($user->access_expires_at)
                <p class="text-sm text-amber-700 mt-1">
                    Access {{ $user->access_expires_at->isPast() ? 'expired' : 'expires' }} {{ $user->access_expires_at->timezone('Asia/Manila')->format('M d, Y') }}
                    ({{ $user->access_expires_at->diffForHumans() }}).
                </p>
            @endif
        </div>

        <div class="p-6 space-y-5">
            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $info['message'] }}</p>

            @if ($info['gcash'] || $info['bank'] || $info['contact'])
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3 text-sm">
                    <p class="font-semibold text-slate-800">Payment details</p>
                    @if ($info['gcash'])
                        <div class="flex items-start gap-2">
                            <span class="font-medium text-slate-500 w-20 flex-shrink-0">GCash</span>
                            <span class="text-slate-800 whitespace-pre-line min-w-0 break-words">{!! $linkify($info['gcash']) !!}</span>
                        </div>
                    @endif
                    @if ($info['bank'])
                        <div class="flex items-start gap-2">
                            <span class="font-medium text-slate-500 w-20 flex-shrink-0">Bank</span>
                            <span class="text-slate-800 whitespace-pre-line min-w-0 break-words">{!! $linkify($info['bank']) !!}</span>
                        </div>
                    @endif
                    @if ($info['contact'])
                        <div class="flex items-start gap-2">
                            <span class="font-medium text-slate-500 w-20 flex-shrink-0">Contact</span>
                            <span class="text-slate-800 whitespace-pre-line min-w-0 break-words">{!! $linkify($info['contact']) !!}</span>
                        </div>
                    @endif
                </div>
            @endif

            <p class="text-xs text-slate-400">
                After paying, send your proof of payment to the contact above. Your account
                <strong>({{ $user->email }})</strong> will be reactivated once confirmed.
            </p>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Log out
                </button>
            </form>
        </div>
    </div>
</div>
