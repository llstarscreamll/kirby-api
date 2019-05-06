<?php

namespace Employees;

use Illuminate\Support\Facades\Queue;
use llstarscreamll\Employees\Jobs\SyncEmployeesByCsvFileJob;

/**
 * Class SyncEmployeesByScvFileCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SyncEmployeesByScvFileCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/employees/sync-by-csv-file';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testToSendValidFile(ApiTester $I)
    {
        Queue::fake();

        $I->sendPOST($this->endpoint, [], ['csv_file' => codecept_data_dir('import_employees/good_employees.csv')]);

        $I->seeResponseCodeIs(202);
        Queue::assertPushed(SyncEmployeesByCsvFileJob::class);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testWithoutFile(ApiTester $I)
    {
        $I->sendPOST($this->endpoint, []);

        $I->seeResponseCodeIs(422);
    }
}
