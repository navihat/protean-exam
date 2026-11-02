<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quản trị</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 space-y-4">
                <p>Xin chào {{ Auth::user()->name }}. Đây là khu vực chỉ dành cho admin.</p>
                <a href="{{ route('admin.exams.index') }}" class="inline-block text-indigo-600 hover:underline">Quản lý đề thi →</a>
            </div>
        </div>
    </div>
</x-app-layout>
