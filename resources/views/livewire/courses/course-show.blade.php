<div x-data="{
        beat() {
            fetch('{{ route('session.ping') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(r => { if (r.status === 409 || r.status === 401) { window.location.href = '{{ route('login') }}'; } })
                .catch(() => {});
        },
        /* Mark the lesson as still open. Skipped while the tab is in the background, so
           leaving a tab parked overnight doesn't read as hours of watching. The server
           enforces the real interval regardless of how often this fires. */
        watchBeat() {
            if (document.visibilityState !== 'visible') return;
            $wire.heartbeat();
        }
     }"
     x-init="beat();
             const t = setInterval(() => beat(), 10000);
             const w = setInterval(() => watchBeat(), 60000);
             window.addEventListener('beforeunload', () => { clearInterval(t); clearInterval(w); });">
    <div class="bg-slate-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <a href="{{ route('tools.courses') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">← All courses</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">🎓 {{ $course->title }}</h1>
        </div>
    </div>

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
                            <div class="course-desc mt-2 text-sm leading-relaxed text-gray-600">
                                {!! \App\Support\RichText::render($currentLesson->description) !!}
                            </div>
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

        @if ($course->description)
            <div class="mt-8 border-t border-gray-100 pt-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400 mb-3">About this course</h2>
                <div class="course-desc max-w-3xl text-sm leading-relaxed text-gray-700">
                    {!! \App\Support\RichText::render($course->description) !!}
                </div>
            </div>
        @endif
    </div>

    <style>
        .course-desc p { margin: 0.5rem 0; }
        .course-desc h1 { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #111827; }
        .course-desc h2 { font-size: 1.25rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #111827; }
        .course-desc h3 { font-size: 1.1rem; font-weight: 600; margin: 0.75rem 0 0.5rem; color: #111827; }
        .course-desc ul { list-style: disc; margin: 0.5rem 0 0.5rem 1.5rem; }
        .course-desc ol { list-style: decimal; margin: 0.5rem 0 0.5rem 1.5rem; }
        .course-desc li { margin: 0.25rem 0; }
        .course-desc strong, .course-desc b { font-weight: 700; }
        .course-desc em, .course-desc i { font-style: italic; }
        .course-desc u { text-decoration: underline; }
        .course-desc s, .course-desc strike, .course-desc del { text-decoration: line-through; }
        .course-desc a { color: #4f46e5; text-decoration: underline; word-break: break-word; }
        .course-desc blockquote { border-left: 3px solid #e2e8f0; padding-left: 0.75rem; margin: 0.5rem 0; color: #64748b; }
        .course-desc pre { background: #f1f5f9; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.8rem; }
    </style>
</div>
