<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Models;

use Carbon\CarbonInterface;
use He4rt\Community\Database\Factories\RetrospectiveFactory;
use He4rt\Community\Retrospective\Casts\AsDeckConfig;
use He4rt\Community\Retrospective\Casts\AsRetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $title
 * @property CarbonInterface $since
 * @property CarbonInterface $until
 * @property RetrospectiveStatus $status
 * @property string|null $cover_title
 * @property string|null $cover_intro
 * @property string|null $closing_text
 * @property bool $hide_bots
 * @property DeckConfig $deck_config
 * @property RetrospectiveSnapshot|null $snapshot
 * @property CarbonInterface|null $published_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: RetrospectiveFactory::class)]
#[Table(name: 'community_retrospectives')]
final class Retrospective extends Model
{
    /** @use HasFactory<RetrospectiveFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'title',
        'since',
        'until',
        'status',
        'cover_title',
        'cover_intro',
        'closing_text',
        'hide_bots',
        'deck_config',
        'snapshot',
        'published_at',
    ];

    /**
     * Recorte temporal da edição como DTO de domínio (CarbonImmutable), pronto
     * para alimentar o collect() das fontes.
     */
    public function period(): Period
    {
        return new Period($this->since->toImmutable(), $this->until->toImmutable());
    }

    /**
     * Filtros que mexem no dado, projetados da edição para o collect(): ocultar
     * bots (coluna) + exclusions curadas (deck_config).
     */
    public function filters(): SourceFilters
    {
        return new SourceFilters(
            hideBots: $this->hide_bots,
            exclusions: $this->deck_config->allExclusions(),
        );
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    /**
     * A edição publicada está exibindo números que não correspondem mais à curadoria
     * atual, porque um filtro que MEXE NO DADO mudou depois do publish.
     *
     * Só olha os SourceFilters: ordem e on/off re-derivam do snapshot na composição e
     * nunca pedem republicação (ADR-0002). Comparar `updated_at` com `published_at`
     * avisaria também nesses casos, apagando justo a distinção que a fase inteira
     * defende.
     */
    public function needsRepublish(): bool
    {
        if (!$this->isPublished() || !$this->snapshot instanceof RetrospectiveSnapshot) {
            return false;
        }

        $frozen = $this->snapshot->filters;
        $current = $this->filters();

        return $frozen->hideBots !== $current->hideBots
            || array_values(array_diff($frozen->exclusions, $current->exclusions)) !== []
            || array_values(array_diff($current->exclusions, $frozen->exclusions)) !== [];
    }

    /**
     * @param  Builder<Retrospective>  $query
     * @return Builder<Retrospective>
     */
    protected function scopePublished(Builder $query): Builder
    {
        return $query->where('status', RetrospectiveStatus::Published->value);
    }

    protected function casts(): array
    {
        return [
            'since' => 'datetime',
            'until' => 'datetime',
            'published_at' => 'datetime',
            'status' => RetrospectiveStatus::class,
            'hide_bots' => 'boolean',
            'deck_config' => AsDeckConfig::class,
            'snapshot' => AsRetrospectiveSnapshot::class,
        ];
    }
}
