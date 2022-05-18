<?php

namespace Kirby\Authorization\Tests\Feature\API\V1;

use Kirby\Authorization\Models\Role;
use Tests\TestCase;

/**
 * @internal
 */
class SearchRolesTest extends TestCase
{
    private $method = 'GET';
    private $endpoint = 'api/v1/roles';

    /** @test */
    public function shouldReturnRolesSuccessfully()
    {
        $this->actingAsAdmin();
        $role = Role::create(['name' => 'Admin']);

        $this
            ->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonPath('data.0.id', $role->id)
            ->assertJsonPath('data.0.name', $role->name)
            ->assertJsonPath('data.0.display_name', $role->display_name);
    }

    /** @test */
    public function shouldForbiddenWhenUserIsNotAuthenticated()
    {
        $this->json($this->method, $this->endpoint)->assertUnauthorized();
    }
}
