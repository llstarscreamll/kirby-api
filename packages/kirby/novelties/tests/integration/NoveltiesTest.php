<?php

namespace Kirby\Novelties\Tests;

use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Novelties;
use NoveltiesPackageSeed;
use Tests\TestCase;

/**
 * Class NoveltiesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class NoveltiesTest extends TestCase
{
    /**
     * @test
     */
    public function shouldReturnRawSettings()
    {
        $this->artisan('db:seed', ['--class' => NoveltiesPackageSeed::class]);
        $novelties = NoveltyType::all();

        $result = app(Novelties::class)->rawSettings()->pluck('key');

        $this->assertCount(4, $result, 'default returned settings count is 4');
        $this->assertContains('novelties.default-addition-novelty-type', $result);
        $this->assertContains('novelties.default-subtraction-novelty-type', $result);
        $this->assertContains('novelties.default-addition-balance-novelty-type', $result);
        $this->assertContains('novelties.default-subtraction-balance-novelty-type', $result);
    }

    /**
     * @test
     */
    public function shouldReturnSettings()
    {
        $this->artisan('db:seed', ['--class' => NoveltiesPackageSeed::class]);
        $novelties = NoveltyType::all();

        $result = data_get(app(Novelties::class)->settings(), '*.value.id');

        $this->assertContains($novelties->firstWhere('code', 'HADI')->id, $result);
        $this->assertContains($novelties->firstWhere('code', 'PP')->id, $result);
        $this->assertContains($novelties->firstWhere('code', 'B+')->id, $result);
        $this->assertContains($novelties->firstWhere('code', 'B-')->id, $result);
    }
}
