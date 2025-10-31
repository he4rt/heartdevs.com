<?php

declare(strict_types=1);

namespace He4rt\Season\Repositories;

use He4rt\Season\Collections\SeasonCollection;
use He4rt\Season\Contracts\SeasonRepository;
use He4rt\Season\Entities\SeasonEntity;
use He4rt\Season\Models\Season;
use Illuminate\Database\Eloquent\Builder;

final readonly class SeasonEloquentRepository implements SeasonRepository
{
    private Builder $query;

    public function __construct(private Season $model)
    {
        $this->query = $this->model->newQuery();
    }

    public function getAll(): SeasonCollection
    {
        $collection = $this->query->get();

        return SeasonCollection::make($collection->toArray());
    }

    public function getCurrent(): SeasonEntity
    {
        $model = $this->query->find(config('he4rt.season.id'));

        return SeasonEntity::make($model->toArray());
    }
}
