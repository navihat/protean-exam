{{--
    Shared by create and edit. Expects $question (with options loaded when editing).
    Options are an Alpine.js list so the admin can add/remove rows without reloading.
    Each checkbox is preceded by a hidden "0" so unchecked options still submit is_correct.
--}}
@php
    $options = old('options', $question->exists
        ? $question->options->map(fn ($o) => ['content' => $o->content, 'is_correct' => $o->is_correct])->all()
        : array_fill(0, 4, ['content' => '', 'is_correct' => false]));
    $options = array_map(fn ($o) => [
        'content' => $o['content'] ?? '',
        'is_correct' => filter_var($o['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ], array_values($options));
@endphp

<div class="space-y-4">
    <div>
        <x-input-label for="content" value="Nội dung câu hỏi" />
        <textarea id="content" name="content" rows="3" required
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('content', $question->content) }}</textarea>
        <x-input-error :messages="$errors->get('content')" class="mt-2" />
    </div>

    <div x-data="{ options: @js($options), min: {{ \App\Http\Requests\Admin\StoreQuestionRequest::MIN_OPTIONS }}, max: {{ \App\Http\Requests\Admin\StoreQuestionRequest::MAX_OPTIONS }} }">
        <x-input-label value="Đáp án (tick vào đáp án đúng, có thể chọn nhiều)" />
        <x-input-error :messages="$errors->get('options')" class="mt-2" />

        <div class="mt-2 space-y-2">
            <template x-for="(option, index) in options" :key="index">
                <div class="flex items-center gap-2">
                    <input type="hidden" :name="`options[${index}][is_correct]`" value="0">
                    <input type="checkbox" value="1" x-model="option.is_correct" :name="`options[${index}][is_correct]`"
                        class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500" title="Đáp án đúng">
                    <input type="text" x-model="option.content" :name="`options[${index}][content]`" required maxlength="500"
                        :placeholder="`Đáp án ${index + 1}`"
                        class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <button type="button" x-show="options.length > min" @click="options.splice(index, 1)"
                        class="text-sm text-red-600 hover:underline">Xoá</button>
                </div>
            </template>
        </div>
        <x-input-error :messages="collect($errors->get('options.*'))->flatten()->unique()->all()" class="mt-2" />

        <button type="button" x-show="options.length < max" @click="options.push({ content: '', is_correct: false })"
            class="mt-3 text-sm text-indigo-600 hover:underline">+ Thêm đáp án</button>
    </div>
</div>
