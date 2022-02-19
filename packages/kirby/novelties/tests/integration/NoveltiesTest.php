<?php

namespace Kirby\Novelties\Tests;

use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Novelties;
use NoveltiesPackageSeed;

/**
 * Class NoveltiesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class NoveltiesTest extends \Tests\TestCase
{
    /**
     * @test
     */
    public function shouldReturnRawSettings()
    {
        $this->artisan('db:seed', ['--class' => NoveltiesPackageSeed::class]);
        $novelties = NoveltyType::all();

        $result = app(Novelties::class)->rawSettings();

        $this->assertCount(4, $result, 'default returned settings count is 4');
        $this->assertEquals('novelties.default-addition-novelty-type', $result[0]->key);
        $this->assertEquals('novelties.default-subtraction-novelty-type', $result[1]->key);
        $this->assertEquals('novelties.default-addition-balance-novelty-type', $result[2]->key);
        $this->assertEquals('novelties.default-subtraction-balance-novelty-type', $result[3]->key);
    }

    /**
     * @test
     */
    public function shouldReturnSettings()
    {
        $this->artisan('db:seed', ['--class' => NoveltiesPackageSeed::class]);
        $novelties = NoveltyType::all();

        $result = app(Novelties::class)->settings();

        $this->assertEquals($novelties->firstWhere('code', 'HADI')->id, $result['0']['value']['id']);
        $this->assertEquals($novelties->firstWhere('code', 'PP')->id, $result['1']['value']['id']);
        $this->assertEquals($novelties->firstWhere('code', 'B+')->id, $result['2']['value']['id']);
        $this->assertEquals($novelties->firstWhere('code', 'B-')->id, $result['3']['value']['id']);
    }
}
