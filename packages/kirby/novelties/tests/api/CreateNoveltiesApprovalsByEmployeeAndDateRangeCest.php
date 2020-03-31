<?php

namespace Novelties;

use Illuminate\Support\Facades\Artisan;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use NoveltiesPermissionsSeeder;

/**
 * Class CreateNoveltiesApprovalsByEmployeeAndDateRangeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesApprovalsByEmployeeAndDateRangeCest
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
            'scheduled_start_at' => now()->subDay()->setTime(07, 00),
            'scheduled_end_at' => now()->subDay()->setTime(10, 00),
        ]);

        $this->steveRogersNoveltiesFromLastMonth = factory(Novelty::class, 2)->create([
            'employee_id' => $this->steveRogers->id,
            'scheduled_start_at' => now()->subMonth()->setTime(07, 00),
            'scheduled_end_at' => now()->subMonth()->setTime(10, 00),
        ]);

        $this->tonyStarkNovelties = factory(Novelty::class, 2)->create([
            'employee_id' => $this->tonyStark->id,
            'scheduled_start_at' => now()->subDay()->setTime(07, 00),
            'scheduled_end_at' => now()->subDay()->setTime(10, 00),
        ]);

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldCreateApprovalsSuccessfully(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, [
            'employee_id' => $this->steveRogers->id,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(2),
        ]);

        $I->seeResponseCodeIs(201);
        $I->seeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromYesterday->first()->id,
        ]);
        $I->seeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromYesterday->last()->id,
        ]);

        // las month novelties should not be approved
        $I->dontSeeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromLastMonth->first()->id,
        ]);
        $I->dontSeeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromLastMonth->last()->id,
        ]);

        // Tony novelties should not be approved
        $I->dontSeeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->tonyStarkNovelties->first()->id,
        ]);
        $I->dontSeeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->tonyStarkNovelties->last()->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnathorizedIfUserDoesntHaveRequiredPermission(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendPOST($this->endpoint, []);

        $I->seeResponseCodeIs(403);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityIfEmployeeDoesntExists(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, [
            'employee_id' => 111111,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(2),
        ]);

        $I->seeResponseCodeIs(422);
    }
}
