<div x-data="{
        beat() {
            fetch('{{ route('session.ping') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(r => { if (r.status === 409 || r.status === 401) { window.location.href = '{{ route('login') }}'; } })
                .catch(() => {});
        }
     }"
     x-init="beat(); const t = setInterval(() => beat(), 10000); window.addEventListener('beforeunload', () => clearInterval(t));">
    <div class="bg-slate-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <a href="{{ route('tools.courses') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">← All courses</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">🎓 {{ $course->title }}</h1>
            @if ($course->description)
                <p class="text-sm text-gray-500">{{ $course->description }}</p>
            @endif
        </div>
    </div>

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{-- Anti-sharing monitoring notice --}}
        <div class="mb-4 flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <span>
                <strong class="font-semibold">This session is actively monitored.</strong>
                Your login status, IP address, location, device, and viewing activity are recorded, and every video is
                watermarked to your account (<span class="font-medium">{{ auth()->user()->email }}</span>).
                Only one device can watch at a time. Sharing your account or credentials may result in
                <strong class="font-semibold">immediate suspension</strong>.
            </span>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">

            {{-- Player --}}
            <div class="lg:flex-1 min-w-0">
                @if ($error)
                    <div class="aspect-video rounded-xl bg-slate-900 flex items-center justify-center text-center p-6">
                        <p class="text-sm text-red-300">⚠️ {{ $error }}</p>
                    </div>
                @elseif ($otp && $playbackInfo)
                    <div class="aspect-video rounded-xl overflow-hidden bg-black shadow" wire:key="player-{{ substr($otp, 0, 12) }}">
                        <iframe src="https://player.vdocipher.com/v2/?otp={{ urlencode($otp) }}&playbackInfo={{ urlencode($playbackInfo) }}"
                                style="border:0;width:100%;height:100%;"
                                allow="encrypted-media" allowfullscreen></iframe>
                    </div>
                @else
                    <div class="aspect-video rounded-xl bg-slate-100 flex items-center justify-center text-gray-400">
                        Select a lesson to start.
                    </div>
                @endif

                <div wire:loading wire:target="selectLesson" class="mt-2 text-xs text-gray-400">Loading video…</div>

                @if ($currentLesson)
                    <div class="mt-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $currentLesson->title }}</h2>
                        @if ($currentLesson->description)
                            <p class="mt-1 text-sm text-gray-600 whitespace-pre-wrap">{{ $currentLesson->description }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Lessons list --}}
            <div class="lg:w-80 lg:flex-shrink-0">
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-700">
                        Lessons ({{ $lessons->count() }})
                    </div>
                    <div class="max-h-[70vh] overflow-y-auto divide-y divide-gray-100">
                        @forelse ($lessons as $i => $lesson)
                            <button type="button" wire:click="selectLesson({{ $lesson->id }})"
                                    class="w-full text-left px-4 py-3 flex items-start gap-3 hover:bg-gray-50 transition
                                           {{ $lessonId === $lesson->id ? 'bg-indigo-50' : '' }}">
                                <span class="flex-shrink-0 mt-0.5 w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold
                                             {{ $lessonId === $lesson->id ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $i + 1 }}</span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium {{ $lessonId === $lesson->id ? 'text-indigo-700' : 'text-gray-800' }} truncate">{{ $lesson->title }}</span>
                                    @if ($lesson->is_free_preview)
                                        <span class="text-[10px] font-semibold uppercase text-emerald-600">Free preview</span>
                                    @endif
                                </span>
                            </button>
                        @empty
                            <p class="px-4 py-6 text-center text-sm text-gray-400">No lessons yet.</p>
                        @endforelse
                    </div>
                </div>
                <p class="mt-2 text-[11px] text-gray-400">🔒 Protected video · watermarked to your account.</p>
            </div>
        </div>
    </div>
</div>
