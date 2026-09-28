<?php

declare(strict_types=1);

namespace He4rt\Squads\Tests\Support;

trait WithoutDatabaseTransactions
{
    /**
     * Worker connections cannot see fixtures inside the test transaction.
     *
     * @var list<string>
     */
    protected $connectionsToTransact = [];
}
