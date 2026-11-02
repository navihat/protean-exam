<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Đề thi</h2>
            <a href="{{ route('admin.exams.create') }}">
                <x-primary-button type="button">+ Tạo đề</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" action="{{ route('admin.exams.index') }}" class="mb-4 flex gap-2">
                <x-text-input name="q" value="{{ $search }}" placeholder="Tìm theo tên đề…" class="w-full sm:w-80" />
                <x-secondary-button type="submit">Tìm</x-secondary-button>
                @if ($search)
                    <a href="{{ route('admin.exams.index') }}" class="self-center text-sm text-gray-600 hover:underline">Bỏ lọc</a>
                @endif
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Tên đề</th>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Số câu</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($exams as $exam)
                            <tr>
                                <td class="px-4 py-3 font-medium">
                                    <a href="{{ route('admin.exams.show', $exam) }}" class="text-indigo-600 hover:underline">{{ $exam->name }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $exam->duration_minutes }} phút</td>
                                <td class="px-4 py-3">{{ $exam->questions_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($exam->is_published)
                                        <span class="rounded bg-green-100 px-2 py-0.5 text-green-800">Đã publish</span>
                                    @else
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">Nháp</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.exams.edit', $exam) }}" class="text-gray-700 hover:underline">Sửa</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    {{ $search ? 'Không tìm thấy đề nào khớp "'.$search.'".' : 'Chưa có đề thi nào.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $exams->links() }}</div>
        </div>
    </div>
</x-app-layout>
