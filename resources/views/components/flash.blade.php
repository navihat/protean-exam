@if (session('status'))
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800']) }} role="status">
        {{ session('status') }}
    </div>
@endif
