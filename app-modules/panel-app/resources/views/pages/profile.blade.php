<x-filament::page>
    <div class="text-sm text-gray-500 dark:text-gray-400">
        Preencha os campos e veja seu card sendo montado em tempo real
    </div>

    <div class="grid grid-cols-1 items-start gap-8 xl:grid-cols-3">
        {{-- Form (left, 2/3) --}}
        <div class="space-y-6 xl:col-span-2">
            @include ('panel-app::components.profile-media-header',
                [
                    'avatarPreviewUrl' => $this->avatarPreviewUrl,
                    'coverPreviewUrl' => $this->coverPreviewUrl,
                    'initials' => $this->initials,
                    'name' => auth()->user()->name
                ])

            {{ $this->form }}
        </div>

        {{-- Preview card + connections (right, 1/3, sticky) --}}
        <div class="hidden space-y-6 xl:sticky xl:top-4 xl:block">
            @include ('panel-app::components.profile-preview-card',
                [
                    'data' => $this->data,
                    'user' => auth()->user(),
                    'character' => $this->character,
                    'initials' => $this->initials,
                    'avatarPreviewUrl' => $this->avatarPreviewUrl,
                    'coverPreviewUrl' => $this->coverPreviewUrl
                ])

            <div class="rounded-xl bg-gray-900 p-4">
                <h3 class="mb-3 text-sm font-semibold text-white">
                    {{ __('panel-app::profile.sections.connections') }}
                </h3>
                <livewire:connection-hub />
            </div>
        </div>
    </div>
</x-filament::page>
