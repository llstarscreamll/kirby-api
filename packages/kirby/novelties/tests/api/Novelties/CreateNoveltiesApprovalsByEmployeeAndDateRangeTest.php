<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use NoveltiesPackageSeed;

/**
 * Class CreateNoveltiesApprovalsByEmployeeAndDateRangeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesApprovalsByEmployeeAndDateRangeTest extends \Tests\TestCase
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

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);

        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());

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

        $this->tonyStarkNovelties->first()->noveltyType->update(['context_type' => 'normal_work_shift_time']);
        $this->steveRogersNoveltiesFromYesterday->first()->noveltyType->update(['context_type' => 'normal_work_shift_time']);
        $this->steveRogersNoveltiesFromLastMonth->first()->noveltyType->update(['context_type' => 'normal_work_shift_time']);
    }

    /**
     * @test
     */
    public function shouldCreateApprovalsSuccessfully()
    {
        $this->json('POST', $this->endpoint, [
            'employee_id' => $this->steveRogers->id,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(2),
        ])->assertCreated();

        $this->assertDatabaseHas('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromYesterday->first()->id,
        ]);

        $this->assertDatabaseHas('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromYesterday->last()->id,
        ]);

        // las month novelties should not be approved
        $this->assertDatabaseMissing('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromLastMonth->first()->id,
        ]);

        $this->assertDatabaseMissing('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->steveRogersNoveltiesFromLastMonth->last()->id,
        ]);

        // Tony novelties should not be approved
        $this->assertDatabaseMissing('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->tonyStarkNovelties->first()->id,
        ]);

        $this->assertDatabaseMissing('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->tonyStarkNovelties->last()->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->actingAsGuest()
            ->json('POST', $this->endpoint, [])
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function shouldReturnUnprocesableEntityIfEmployeeDoesntExists()
    {
        $this->json('POST', $this->endpoint, [
            'employee_id' => 111111,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(2),
        ])->assertStatus(422);
    }
}
