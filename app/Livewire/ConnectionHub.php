<?php

declare(strict_types=1);

namespace App\Livewire;

use Filament\Notifications\Notification;
use He4rt\Identity\Auth\Actions\MergeAccountsAction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ConnectionHub extends Component
{
    public string $panel = 'app';

    public string $tenantId = '';

    public bool $showMergeModal = false;

    /** @var array<string, mixed>|null */
    public ?array $mergeData = null;

    public function mount(): void
    {
        $this->panel = filament()->getCurrentPanel()?->getId() ?? 'app';
        $this->tenantId = (string) config('he4rt.tenant_id');
        $this->checkPendingMerge();
    }

    public function render(): View
    {
        $supportedProviders = IdentityProvider::supportedProviders();

        if ($this->panel === 'admin') {
            return view('livewire.connection-hub-admin', [
                'tenantProviders' => $this->getTenantProviders(),
                'supportedProviders' => $supportedProviders,
                'panel' => $this->panel,
            ]);
        }

        return view('livewire.connection-hub', [
            'userProviders' => $this->getUserProviders(),
            'supportedProviders' => $supportedProviders,
            'panel' => $this->panel,
            'mergeTarget' => $this->getMergeTarget(),
        ]);
    }

    public function connect(IdentityProvider $provider): void
    {
        $this->redirect(route('oauth.redirect', [
            'panel' => $this->panel,
            'provider' => $provider->value,
        ]));
    }

    public function confirmMerge(MergeAccountsAction $action): void
    {
        if ($this->mergeData === null) {
            return;
        }

        $oldUser = User::query()->find($this->mergeData['conflicting_user_id']);

        if (!$oldUser instanceof User) {
            $this->cancelMerge();

            return;
        }

        /** @var User $currentUser */
        $currentUser = auth()->user();

        $action->execute($currentUser, $oldUser);

        session()->forget('oauth_merge_pending');
        $this->showMergeModal = false;
        $this->mergeData = null;

        Auth::login($oldUser);

        Notification::make()
            ->title('Contas unificadas com sucesso')
            ->success()
            ->send();

        $this->redirect(filament()->getCurrentPanel()->getUrl());
    }

    public function cancelMerge(): void
    {
        session()->forget('oauth_merge_pending');
        $this->showMergeModal = false;
        $this->mergeData = null;
    }

    public function disconnect(IdentityProvider $provider): void
    {
        $identity = auth()->user()
            ->providers()
            ->where('tenant_id', $this->tenantId)
            ->where('provider', $provider->value)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        if (!$identity) {
            Notification::make()
                ->title('No active connection found for '.$provider->getLabel())
                ->warning()
                ->send();

            return;
        }

        $identity->update(['disconnected_at' => now()]);

        Notification::make()
            ->title($provider->getLabel().' disconnected successfully')
            ->success()
            ->send();
    }

    public function disconnectById(string $identityId): void
    {
        $identity = ExternalIdentity::query()
            ->where('id', $identityId)
            ->where('tenant_id', $this->tenantId)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        if (!$identity) {
            Notification::make()
                ->title('Connection not found')
                ->warning()
                ->send();

            return;
        }

        $identity->update(['disconnected_at' => now()]);

        Notification::make()
            ->title($identity->provider->getLabel().' disconnected successfully')
            ->success()
            ->send();
    }

    private function checkPendingMerge(): void
    {
        $pending = session()->get('oauth_merge_pending');

        if ($pending === null) {
            return;
        }

        $this->mergeData = $pending;
        $this->showMergeModal = true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getMergeTarget(): ?array
    {
        if ($this->mergeData === null) {
            return null;
        }

        $user = User::query()->find($this->mergeData['conflicting_user_id']);

        if (!$user instanceof User) {
            return null;
        }

        $messagesCount = ExternalIdentity::query()
            ->where('model_type', (new User)->getMorphClass())
            ->where('model_id', $user->id)
            ->withCount('messages')
            ->get()
            ->sum('messages_count');

        return [
            'username' => $user->username,
            'created_at' => $user->created_at?->format('d/m/Y'),
            'messages_count' => $messagesCount,
        ];
    }

    /** @return Collection<int, ExternalIdentity> */
    private function getUserProviders(): Collection
    {
        return auth()->user()->providers()->where('tenant_id', $this->tenantId)->get();
    }

    /** @return Collection<int, ExternalIdentity> */
    private function getTenantProviders(): Collection
    {
        return ExternalIdentity::query()
            ->where('tenant_id', $this->tenantId)
            ->where('model_type', 'tenant')
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->with('connectedByUser')
            ->get();
    }
}
