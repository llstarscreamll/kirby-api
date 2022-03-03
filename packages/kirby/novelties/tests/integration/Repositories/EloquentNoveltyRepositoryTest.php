<?php

namespace Kirby\Novelties\Tests\Listeners;

use DefaultNoveltyTypesSeed;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Repositories\EloquentNoveltyRepository;
use Kirby\Users\Models\User;

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
        $approver = factory(User::class)->create();

        $repository = app(EloquentNoveltyRepository::class);
        $repository->setApprovals($novelties->take(3)->pluck('id')->all(), $approver->id);

        $this->assertDatabaseRecordsCount(3, 'novelty_approvals', ['user_id' => $approver->id]);
        // last record should not be approved
        $this->assertDatabaseMissing('novelty_approvals', ['novelty_id' => $novelties->last()->id, 'user_id' => $approver->id]);
    }

    /**
     * @test
     */
    public function deleteApprovals()
    {
        $approver = factory(User::class)->create();
        $novelties = factory(Novelty::class, 4)->create();
        $novelties->each->approve($approver->id);

        $repository = app(EloquentNoveltyRepository::class);
        $repository->deleteApprovals($novelties->take(3)->pluck('id')->all(), $approver->id);

        // only one record still present with the approved novelty
        $this->assertDatabaseRecordsCount(1, 'novelty_approvals');
        $this->assertDatabaseRecordsCount(1, 'novelty_approvals', ['user_id' => $approver->id]);
    }
}
