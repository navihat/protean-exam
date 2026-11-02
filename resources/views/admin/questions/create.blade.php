<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Thêm câu hỏi · {{ $exam->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.exams.questions.store', $exam) }}" class="bg-white shadow-sm sm:rounded-lg p-6">
                @csrf
                @include('admin.questions._form')
                <div class="mt-6 flex gap-3">
                    <x-primary-button>Lưu</x-primary-button>
                    <a href="{{ route('admin.exams.show', $exam) }}" class="self-center text-sm text-gray-600 hover:underline">Huỷ</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
