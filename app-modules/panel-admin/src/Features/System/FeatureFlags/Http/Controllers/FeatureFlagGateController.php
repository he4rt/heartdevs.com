<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Controllers;

use App\Enums\ToastType;
use App\Http\Controllers\Concerns\FlashesToast;
use App\Support\FeatureFlags\Actions\CloseFeatureGate;
use App\Support\FeatureFlags\Actions\OpenFeatureGate;
use App\Support\FeatureFlags\FeatureFlag;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\CreateFeatureFlagGateRequest;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\DeleteFeatureFlagGateRequest;
use Illuminate\Http\RedirectResponse;

final readonly class FeatureFlagGateController
{
    use FlashesToast;

    public function store(CreateFeatureFlagGateRequest $request, FeatureFlag $flag, OpenFeatureGate $openGate): RedirectResponse
    {
        $openGate->handle($flag);

        return $this->back(sprintf('%s gate opened', $flag->label()));
    }

    public function destroy(DeleteFeatureFlagGateRequest $request, FeatureFlag $flag, CloseFeatureGate $closeGate): RedirectResponse
    {
        $closeGate->handle($flag);

        return $this->back(sprintf('%s gate closed', $flag->label()), ToastType::Warning);
    }
}
