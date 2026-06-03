<div>
    @if ($this->canCheckIn)
        <div class="rounded-xl border border-gray-200 p-5 dark:border-white/10">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                {{ __('events::pages.enter_check_in_code_hint') }}
            </p>

            <div class="flex items-start gap-3">
                <div class="flex-1">
                    <x-filament::input.wrapper :valid="!$errors->has('code') && $error === null">
                        <x-filament::input
                            type="text"
                            wire:model="code"
                            placeholder="000000"
                            maxlength="6"
                            class="text-center text-lg tracking-widest"
                        />
                    </x-filament::input.wrapper>

                    @error ('code')
                        <p class="text-danger-600 dark:text-danger-400 mt-2 text-sm">{{ $message }}</p>
                    @enderror

                    @if ($error)
                        <p class="text-danger-600 dark:text-danger-400 mt-2 text-sm">{{ $error }}</p>
                    @endif
                </div>

                <x-filament::button wire:click="checkIn" wire:loading.attr="disabled">
                    {{ __('events::pages.check_in') }}
                </x-filament::button>
            </div>
        </div>
    @endif
</div>
