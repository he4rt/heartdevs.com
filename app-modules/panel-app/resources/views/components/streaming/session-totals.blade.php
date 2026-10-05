@props([
    'rows',
])

<dl {{ $attributes->class('grid grid-cols-2 gap-3 sm:grid-cols-5') }}>
    @foreach ($rows as $row)
        <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
            <dt class="text-xs text-gray-500 dark:text-gray-400">
                <span aria-hidden="true">{{ $row['emoji'] }}</span>
                {{ $row['label'] }}
            </dt>
            <dd class="flex items-baseline gap-2">
                <span class="text-xl font-semibold text-gray-950 tabular-nums dark:text-white">
                    {{ number_format($row['value'], thousands_separator: '.') }}
                </span>
                @if ($row['delta'] !== null)
                    <span
                        @class([
                            'text-xs font-medium tabular-nums',
                            'text-success-600 dark:text-success-400' => $row['delta'] > 0,
                            'text-danger-600 dark:text-danger-400' => $row['delta'] < 0,
                            'text-gray-400' => $row['delta'] === 0,
                        ])
                    >
                        @if ($row['delta'] > 0)
                            ▲{{ number_format($row['delta'], thousands_separator: '.') }}
                        @elseif ($row['delta'] < 0)
                            ▼{{ number_format(abs($row['delta']), thousands_separator: '.') }}
                        @else
                            =
                        @endif
                    </span>
                @endif
            </dd>
        </div>
    @endforeach
</dl>
