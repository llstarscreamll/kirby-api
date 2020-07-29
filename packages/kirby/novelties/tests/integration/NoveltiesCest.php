<?php

namespace Kirby\Novelties\Tests;

use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Novelties;
use NoveltiesPackageSeed;

/**
 * Class NoveltiesCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesCest
{
    /**
     * @test
     * @param IntegrationTester $I
     */
    public function shouldReturnRawSettings(IntegrationTester $I)
    {
        $I->callArtisan('db:seed', ['--class' => NoveltiesPackageSeed::class]);
        $novelties = NoveltyType::all();

        $result = app(Novelties::class)->rawSettings();

        $I->assertCount(4, $result, 'default returned settings count is 4');
        $I->assertEquals('novelties.default-addition-novelty-type', $result[0]->key);
        $I->assertEquals('novelties.default-subtraction-novelty-type', $result[1]->key);
        $I->assertEquals('novelties.default-addition-balance-novelty-type', $result[2]->key);
        $I->assertEquals('novelties.default-subtraction-balance-novelty-type', $result[3]->key);
    }

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function shouldReturnSettings(IntegrationTester $I)
    {
        $I->callArtisan('db:seed', ['--class' => NoveltiesPackageSeed::class]);
        $novelties = NoveltyType::all();

        $result = app(Novelties::class)->settings();

        $I->assertEquals($novelties->firstWhere('code', 'HADI')->id, $result['0']['value']['id']);
        $I->assertEquals($novelties->firstWhere('code', 'PP')->id, $result['1']['value']['id']);
        $I->assertEquals($novelties->firstWhere('code', 'B+')->id, $result['2']['value']['id']);
        $I->assertEquals($novelties->firstWhere('code', 'B-')->id, $result['3']['value']['id']);
    }
}
