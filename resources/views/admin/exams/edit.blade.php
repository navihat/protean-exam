<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sửa đề: {{ $exam->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="POST" action="{{ route('admin.exams.update', $exam) }}" class="bg-white shadow-sm sm:rounded-lg p-6">
                @csrf
                @method('PUT')
                @include('admin.exams._form')
                <div class="mt-6 flex gap-3">
                    <x-primary-button>Lưu</x-primary-button>
                    <a href="{{ route('admin.exams.show', $exam) }}" class="self-center text-sm text-gray-600 hover:underline">Huỷ</a>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" class="bg-white shadow-sm sm:rounded-lg p-6"
                onsubmit="return confirm('Xoá đề này? Lịch sử làm bài vẫn được giữ.')">
                @csrf
                @method('DELETE')
                <x-danger-button>Xoá đề thi</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
