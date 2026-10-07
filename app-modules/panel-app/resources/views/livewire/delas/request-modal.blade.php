<div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
    <div class="flex justify-center">
        <x-panel-app::delas.logo variant="vertical" class="text-delas-500 h-14 w-auto" :label="__('panel-app::delas.name')" />
    </div>

    <p>{{ __('panel-app::delas.profile.modal.what') }}</p>

    <ul class="list-disc space-y-1 ps-5">
        @foreach (__('panel-app::delas.profile.modal.steps') as $step)
            <li>{{ $step }}</li>
        @endforeach
    </ul>
</div>
