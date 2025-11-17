@props([
    'href' => '/',
    'path' => 'images/logo.svg',
    'size' => 'md',
])

<a href="{{ $href }}">
    <img src="{{ asset($path) }}" alt="logo" class="mb-4 w-full max-w-40 cursor-pointer" />
</a>
