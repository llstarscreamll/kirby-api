<?php

namespace Kirby\Novelties\Tests\Listeners;

use DefaultNoveltyTypesSeed;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Repositories\EloquentNoveltyRepository;

/**
 * Class EloquentNoveltyRepositoryTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class EloquentNoveltyRepositoryTest extends \Tests\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultNoveltyTypesSeed::class);
    }

    /**
     * @test
     */
    public function setApprovals()
    {
        $novelties = factory(Novelty::class, 4)->create();
        $approverId = 5;

        $repository = app(EloquentNoveltyRepository::class);
        $repository->setApprovals($novelties->take(3)->pluck('id')->all(), $approverId);

        $this->assertDatabaseRecordsCount(3, 'novelty_approvals', ['user_id' => $approverId]);
        // last record should not be approved
        $this->assertDatabaseMissing('novelty_approvals', ['novelty_id' => $novelties->last()->id, 'user_id' => $approverId]);
    }

    /**
     * @test
     */
    public function deleteApprovals()
    {
        $approverId = 5;
        $novelties = factory(Novelty::class, 4)->create();
        $novelties->each->approve($approverId);

        $repository = app(EloquentNoveltyRepository::class);
        $repository->deleteApprovals($novelties->take(3)->pluck('id')->all(), $approverId);

        // only one record still present with the approved novelty
        $this->assertDatabaseRecordsCount(1, 'novelty_approvals');
        $this->assertDatabaseRecordsCount(1, 'novelty_approvals', ['user_id' => $approverId]);
    }
}
