<?php

namespace Novelties;

use Illuminate\Support\Facades\Artisan;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use NoveltiesPermissionsSeeder;

/**
 * Class DeleteNoveltiesApprovalsByEmployeeAndDateRangeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltiesApprovalsByEmployeeAndDateRangeCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/approvals-by-employee-and-date-range';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Kirby\Employees\Models\Employee
     */
    private $tonyStark;

    /**
     * @var \Kirby\Employees\Models\Employee
     */
    private $steveRogers;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $steveRogersNoveltiesFromYesterday;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        Artisan::call('db:seed', ['--class' => NoveltiesPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();
        $this->steveRogers = factory(Employee::class)->create();
        $this->tonyStark = factory(Employee::class)->create();

        $this->steveRogersNoveltiesFromYesterday = factory(Novelty::class, 2)->create([
            'employee_id' => $this->steveRogers->id,
            'start_at' => now()->subDay()->setTime(07, 00),
            'end_at' => now()->subDay()->setTime(10, 00),
        ]);

        $this->steveRogersNoveltiesFromLastMonth = factory(Novelty::class, 2)->create([
            'employee_id' => $this->steveRogers->id,
            'start_at' => now()->subMonth()->setTime(07, 00),
            'end_at' => now()->subMonth()->setTime(10, 00),
        ]);

        $this->tonyStarkNovelties = factory(Novelty::class, 2)->create([
            'employee_id' => $this->tonyStark->id,
            'start_at' => now()->subDay()->setTime(07, 00),
            'end_at' => now()->subDay()->setTime(10, 00),
        ]);

        $this->steveRogersNoveltiesFromYesterday->first()->noveltyType->update(['context_type' => 'normal_work_shift_time']);
        $this->tonyStarkNovelties->first()->noveltyType->update(['context_type' => 'normal_work_shift_time']);

        // set approvals
        $this->steveRogersNoveltiesFromYesterday->first()->approvals()->attach($this->user);
        $this->steveRogersNoveltiesFromYesterday->last()->approvals()->attach($this->user);
        $this->steveRogersNoveltiesFromLastMonth->first()->approvals()->attach($this->user);
        $this->steveRogersNoveltiesFromLastMonth->last()->approvals()->attach($this->user);
        $this->tonyStarkNovelties->first()->approvals()->attach($this->user);
        $this->tonyStarkNovelties->last()->approvals()->attach($this->user);

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldDeleteApprovalsSuccessfully(ApiTester $I)
    {
        $I->sendDELETE($this->endpoint, [
            'employee_id' => $this->steveRogers->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        $I->seeResponseCodeIs(200);
        $I->dontSeeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromYesterday->first()->id,
        ]);
        $I->dontSeeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromYesterday->last()->id,
        ]);

        // las month novelties should remain the same
        $I->seeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromLastMonth->first()->id,
        ]);
        $I->seeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromLastMonth->last()->id,
        ]);

        // Tony novelties should remain the same
        $I->seeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->tonyStarkNovelties->first()->id,
        ]);
        $I->seeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->tonyStarkNovelties->last()->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendDELETE($this->endpoint, []);

        $I->seeResponseCodeIs(403);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityIfEmployeeDoesntExists(ApiTester $I)
    {
        $I->sendDELETE($this->endpoint, [
            'employee_id' => 111111,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        $I->seeResponseCodeIs(422);
    }
}
