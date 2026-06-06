<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\Portal\Retrospective\CommunityRetrospective;
use He4rt\Portal\Retrospective\RetrospectiveFilters;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('portal::components.layouts.deck')]
#[Title('Quem fez a He4rt bater')]
final class CommunityRetrospectivePage extends Component
{
    private const array ALL_TYPES = ['pr', 'review', 'issue', 'comment', 'commit'];

    public string $tenantId;

    #[Url]
    public ?string $since = null;

    #[Url]
    public ?string $until = null;

    /** @var list<string> */
    #[Url]
    public array $repos = [];

    /** @var list<string> */
    #[Url]
    public array $types = [];

    #[Url]
    public ?string $outcome = null;

    #[Url]
    public ?string $person = null;

    #[Url]
    public bool $hideBots = true;

    #[Url]
    public string $sort = 'total';

    #[Url]
    public bool $byRepo = true;

    #[Url]
    public bool $showHighlights = true;

    public function mount(?string $tenantSlug = null): void
    {
        $slug = $tenantSlug ?? config()->string('he4rt.main_tenant');

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->where('slug', $slug)->firstOrFail();

        $this->tenantId = $tenant->id;
    }

    public function toggleType(string $type): void
    {
        $this->types = $this->toggleAware($this->types, self::ALL_TYPES, $type);
    }

    public function toggleRepo(string $repo): void
    {
        $this->repos = $this->toggleAware($this->repos, $this->allRepos(), $repo);
    }

    public function setPreset(string $preset): void
    {
        $this->since = match ($preset) {
            'mes' => CarbonImmutable::now()->subMonth()->toDateString(),
            'tudo' => $this->firstContributionDate(),
            default => CarbonImmutable::now()->startOfWeek(CarbonInterface::MONDAY)->subWeek()->toDateString(),
        };

        $this->until = CarbonImmutable::now()->toDateString();
    }

    public function render(): View
    {
        $since = $this->since !== null && $this->since !== ''
            ? CarbonImmutable::parse($this->since)->startOfDay()
            : CarbonImmutable::now()->startOfWeek(CarbonInterface::MONDAY)->subWeek();

        $until = $this->until !== null && $this->until !== ''
            ? CarbonImmutable::parse($this->until)->endOfDay()
            : CarbonImmutable::now();

        $filters = RetrospectiveFilters::make($since, $until, $this->repos, $this->types, $this->outcome, $this->person, $this->hideBots, $this->sort);
        $data = new CommunityRetrospective($this->tenantId, $filters)->build();

        $repoOptions = collect($this->allRepos())
            ->mapWithKeys(fn (string $repo): array => [$repo => (string) str($repo)->afterLast('/')])
            ->all();

        return view('portal::community-retrospective', [
            'data' => $data,
            'repoOptions' => $repoOptions,
            'stateKey' => md5((string) json_encode([
                $this->since, $this->until, $this->repos, $this->types, $this->outcome,
                $this->person, $this->hideBots, $this->sort, $this->byRepo, $this->showHighlights,
            ])),
        ]);
    }

    /**
     * Toggle "ciente de todos": com a lista vazia (= todos), clicar desliga apenas
     * aquele item; voltar a ter todos selecionados normaliza de volta para vazio.
     *
     * @param  list<string>  $selected
     * @param  list<string>  $all
     * @return list<string>
     */
    private function toggleAware(array $selected, array $all, string $value): array
    {
        $current = $selected === [] ? $all : $selected;

        $next = in_array($value, $current, true)
            ? array_values(array_diff($current, [$value]))
            : array_values(array_unique([...$current, $value]));

        return count($next) === count($all) ? [] : $next;
    }

    /**
     * Data da contribuição mais antiga dentro do escopo atual (tenant + repos
     * selecionados), para o preset "tudo" cobrir o histórico real do que está
     * sendo apresentado. Sem registros, cai no mesmo default semanal do render().
     */
    private function firstContributionDate(): string
    {
        $first = GithubContribution::query()
            ->where('tenant_id', $this->tenantId)
            ->when($this->repos !== [], fn ($query) => $query->whereIn('repo', $this->repos))
            ->min('occurred_at');

        return is_string($first) && $first !== ''
            ? CarbonImmutable::parse($first)->toDateString()
            : CarbonImmutable::now()->startOfWeek(CarbonInterface::MONDAY)->subWeek()->toDateString();
    }

    /**
     * @return list<string>
     */
    private function allRepos(): array
    {
        /** @var list<string> $repos */
        $repos = GithubContribution::query()
            ->where('tenant_id', $this->tenantId)
            ->distinct()
            ->orderBy('repo')
            ->pluck('repo')
            ->all();

        return $repos;
    }
}
