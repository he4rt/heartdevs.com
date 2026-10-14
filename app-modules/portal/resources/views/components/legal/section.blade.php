@props([
    'id',
    'number',
    'title',
])

<section id="{{ $id }}" class="border-outline-low scroll-mt-24 border-t pt-8">
    <h2 class="text-text-high flex items-baseline gap-3 text-xl font-bold tracking-tight lg:text-2xl">
        <span class="text-primary font-mono text-sm">{{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</span>
        {{ $title }}
    </h2>

    <div
        class="text-text-medium [&_a]:text-primary [&_strong]:text-text-high mt-4 flex flex-col gap-4 text-base leading-relaxed [&_a]:underline [&_a]:underline-offset-2 [&_h3]:text-text-high [&_h3]:mt-2 [&_h3]:font-semibold [&_li]:ml-5 [&_li]:list-disc [&_ul]:flex [&_ul]:flex-col [&_ul]:gap-2"
    >
        {{ $slot }}
    </div>
</section>
