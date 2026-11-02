<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $exam->name }}</h2>
            <div class="flex gap-3">
                <a href="{{ route('admin.exams.edit', $exam) }}"><x-secondary-button type="button">Sửa đề</x-secondary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-700 space-y-1">
                <p>{{ $exam->description ?: 'Không có mô tả.' }}</p>
                <p>Thời gian: <strong>{{ $exam->duration_minutes }} phút</strong> · {{ $exam->questions->count() }} câu ·
                    {{ $exam->is_published ? 'Đã publish' : 'Nháp' }}</p>
                <a href="{{ route('admin.exams.index') }}" class="inline-block pt-2 text-indigo-600 hover:underline">← Danh sách đề</a>
            </div>

            @forelse ($exam->questions as $question)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-start justify-between gap-4">
                        <p class="font-medium text-gray-900">Câu {{ $loop->iteration }}. {{ $question->content }}</p>
                    </div>
                    <ul class="mt-3 space-y-1 text-sm">
                        @foreach ($question->options as $option)
                            <li @class(['rounded px-3 py-1', 'bg-green-50 text-green-800 font-medium' => $option->is_correct, 'text-gray-700' => ! $option->is_correct])>
                                {{ $option->is_correct ? '✓' : '•' }} {{ $option->content }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    Đề chưa có câu hỏi nào.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
