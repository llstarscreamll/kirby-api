<?php

namespace Novelties\Listeners;

use Mockery;
use Novelties\IntegrationTester;
use llstarscreamll\Novelties\Models\Novelty;
use llstarscreamll\Novelties\Repositories\EloquentNoveltyRepository;

/**
 * Class EloquentNoveltyRepositoryCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentNoveltyRepositoryCest
{
    /**
     * @param IntegrationTester $I
     */
    public function _before(IntegrationTester $I)
    {
        //
    }

    /**
     * @param IntegrationTester $I
     */
    public function _after(IntegrationTester $I)
    {
        Mockery::close();
    }

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function setApprovals(IntegrationTester $I)
    {
        $novelties = factory(Novelty::class, 4)->create();
        $approverId = 5;

        $repository = app(EloquentNoveltyRepository::class);
        $repository->setApprovals($novelties->take(3)->pluck('id')->all(), $approverId);

        $I->seeNumRecords(3, 'novelty_approvals', ['user_id' => $approverId]);
        // last record should not be approved
        $I->dontSeeRecord('novelty_approvals', ['novelty_id' => $novelties->last()->id, 'user_id' => $approverId]);
    }

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function deleteApprovals(IntegrationTester $I)
    {
        $approverId = 5;
        $novelties = factory(Novelty::class, 4)->create();
        $novelties->each->approve($approverId);

        $repository = app(EloquentNoveltyRepository::class);
        $repository->deleteApprovals($novelties->take(3)->pluck('id')->all(), $approverId);

        // only one record still present with the approved novelty
        $I->seeNumRecords(1, 'novelty_approvals');
        $I->seeNumRecords(1, 'novelty_approvals', ['user_id' => $approverId]);
    }
}
