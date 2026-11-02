{{-- Shared by create and edit. Expects $exam. --}}
<div class="space-y-4">
    <div>
        <x-input-label for="name" value="Tên đề" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $exam->name)" required maxlength="255" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Mô tả" />
        <textarea id="description" name="description" rows="3"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $exam->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="duration_minutes" value="Thời gian làm bài (phút)" />
        <x-text-input id="duration_minutes" name="duration_minutes" type="number" min="1" max="300" class="mt-1 block w-40"
            :value="old('duration_minutes', $exam->duration_minutes)" required />
        <x-input-error :messages="$errors->get('duration_minutes')" class="mt-2" />
    </div>

    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_published" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked(old('is_published', $exam->is_published))>
        <span class="text-sm text-gray-700">Publish (student nhìn thấy đề này)</span>
    </label>
</div>
