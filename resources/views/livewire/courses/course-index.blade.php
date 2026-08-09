<div>
    <div class="bg-slate-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-2xl font-bold text-gray-900">🎓 Courses</h1>
            <p class="text-sm text-gray-500">Watch your training videos. Each video is protected and watermarked to your account.</p>
        </div>
    </div>

    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if ($courses->isEmpty())
            <div class="rounded-xl border-2 border-dashed border-gray-200 text-gray-400 text-center p-12">
                No courses available yet.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($courses as $course)
                    <a href="{{ route('tools.courses.show', $course) }}" wire:navigate
                       class="group flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden transition hover:shadow-md hover:-translate-y-0.5 hover:border-indigo-300">
                        <div class="aspect-video bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-4xl">
                            @if ($course->thumbnail_url)
                                <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" class="w-full h-full object-cover">
                            @else
                                🎬
                            @endif
                        </div>
                        <div class="p-5 flex-1 flex flex-col">
                            <h3 class="font-semibold text-slate-900 group-hover:text-indigo-600">{{ $course->title }}</h3>
                            @if ($course->description)
                                <p class="mt-1 text-sm text-slate-500 line-clamp-2">{{ \App\Support\RichText::excerpt($course->description) }}</p>
                            @endif
                            <span class="mt-3 text-xs font-medium text-slate-400">{{ $course->lessons_count }} {{ Str::plural('lesson', $course->lessons_count) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
