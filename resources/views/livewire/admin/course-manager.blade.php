<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-xl font-bold text-slate-900 mb-1">Admin</h1>
    @include('partials.admin-nav')

    @if (session('msg'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2.5 text-sm text-emerald-700">✓ {{ session('msg') }}</div>
    @endif

    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Courses</h2>
            <p class="text-sm text-slate-500">Create courses and add lessons (paste the VdoCipher Video ID per lesson).</p>
        </div>
        <button wire:click="newCourse" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ New course</button>
    </div>

    {{-- Course form --}}
    @if ($showCourseForm)
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-900">{{ $editingCourseId ? 'Edit course' : 'New course' }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
                    <input wire:model="c_title" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('c_title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea wire:model="c_description" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Thumbnail URL <span class="text-xs text-slate-400">(optional)</span></label>
                    <input wire:model="c_thumbnail_url" type="url" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('c_thumbnail_url') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sort order</label>
                    <input wire:model="c_sort" type="number" class="w-32 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <label class="inline-flex items-center gap-2 ml-4 text-sm text-slate-700"><input wire:model="c_published" type="checkbox" class="rounded border-slate-300 text-indigo-600"> Published</label>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="saveCourse" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save course</button>
                <button wire:click="$set('showCourseForm', false)" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
            </div>
        </div>
    @endif

    {{-- Courses list --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-8">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-2 font-medium">Course</th>
                    <th class="text-right px-4 py-2 font-medium">Lessons</th>
                    <th class="text-left px-4 py-2 font-medium">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($courses as $course)
                    <tr class="hover:bg-slate-50 {{ $managingCourseId === $course->id ? 'bg-indigo-50/40' : '' }}">
                        <td class="px-4 py-2 font-medium text-slate-800">{{ $course->title }}</td>
                        <td class="px-4 py-2 text-right tabular-nums text-slate-600">{{ $course->lessons_count }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $course->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $course->is_published ? 'Published' : 'Draft' }}</span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button wire:click="manageLessons({{ $course->id }})" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Lessons</button>
                            <button wire:click="editCourse({{ $course->id }})" class="ml-3 text-xs font-semibold text-slate-500 hover:text-slate-700">Edit</button>
                            <button wire:click="deleteCourse({{ $course->id }})" wire:confirm="Delete this course and all its lessons?" class="ml-3 text-xs font-semibold text-red-500 hover:text-red-700">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No courses yet. Click “New course”.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Lessons of the selected course --}}
    @if ($managingCourse)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-900">Lessons — {{ $managingCourse->title }}</h3>
                <button wire:click="newLesson" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">+ Add lesson</button>
            </div>

            @if ($showLessonForm)
                <div class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Lesson title</label>
                            <input wire:model="l_title" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('l_title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">VdoCipher Video ID</label>
                            <input wire:model="l_video_id" type="text" placeholder="e.g. 18d620469c3ba8f11c869631df73fa1f" class="w-full rounded-lg border-slate-300 text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                            @error('l_video_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Description <span class="text-slate-400">(optional)</span></label>
                            <textarea wire:model="l_description" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Sort order</label>
                            <input wire:model="l_sort" type="number" class="w-28 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <label class="inline-flex items-center gap-2 ml-4 text-sm text-slate-700"><input wire:model="l_free" type="checkbox" class="rounded border-slate-300 text-indigo-600"> Free preview</label>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button wire:click="saveLesson" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save lesson</button>
                        <button wire:click="$set('showLessonForm', false)" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    </div>
                </div>
            @endif

            <div class="divide-y divide-slate-100">
                @forelse ($managingCourse->lessons as $i => $lesson)
                    <div class="flex items-center justify-between py-2.5">
                        <div class="min-w-0">
                            <span class="text-sm font-medium text-slate-800">{{ $i + 1 }}. {{ $lesson->title }}</span>
                            @if ($lesson->is_free_preview)<span class="ml-2 text-[10px] font-semibold uppercase text-emerald-600">Free</span>@endif
                            <span class="block text-xs text-slate-400 font-mono truncate">{{ $lesson->vdocipher_video_id }}</span>
                        </div>
                        <div class="flex-shrink-0 whitespace-nowrap">
                            <button wire:click="editLesson({{ $lesson->id }})" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Edit</button>
                            <button wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="Delete this lesson?" class="ml-3 text-xs font-semibold text-red-500 hover:text-red-700">Delete</button>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">No lessons yet. Click “Add lesson”.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
