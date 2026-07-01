<div class="space-y-4 p-2">
    @if ($record->rejection_reason)
        <div class="bg-danger-50 dark:bg-danger-950 rounded-lg p-3">
            <p class="text-danger-700 dark:text-danger-300 text-sm font-medium">Rejection Reason</p>
            <p class="text-danger-600 dark:text-danger-400 mt-1 text-sm">{{ $record->rejection_reason }}</p>
        </div>
    @endif

    @if (count($answers) > 0)
        <dl class="space-y-3">
            @foreach ($answers as $answer)
                <div>
                    <dt class="text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">
                        {{ $answer['label'] }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        @if (is_bool($answer['value']))
                            {{ $answer['value'] ? 'Yes' : 'No' }}
                        @elseif (is_array($answer['value']))
                            @if ($answer['value'] === [])
                                <span class="text-gray-400 italic">No answer</span>
                            @else
                                {{ implode(', ', $answer['value']) }}
                            @endif
                        @elseif ($answer['value'] === '—' || $answer['value'] === null || $answer['value'] === '')
                            <span class="text-gray-400 italic">No answer</span>
                        @else
                            {{ $answer['value'] }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No application data available.</p>
    @endif
</div>
