<?php

namespace Kirby\Company\Tests\api;

use Kirby\Company\Models\SubCostCenter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SearchSubCostCentersTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchSubCostCentersTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/sub-cost-centers';

    public function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    /**
     * @test
     */
    public function withoutQueryString()
    {
        factory(SubCostCenter::class, 5)->create();

        $this->json('GET', $this->endpoint)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonHasPath('data.0.id')
            ->assertJsonHasPath('meta')
            ->assertJsonHasPath('links');
    }
}
