<?php

namespace Kirby\Authentication\Tests\api;

use AuthorizationPackageSeeder;
use Kirby\Authorization\Models\Permission;
use Kirby\Authorization\Models\Role;
use Kirby\Users\Models\User;

/**
 * Class GetUserTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class GetUserTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/auth/user';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthorizationPackageSeeder::class);

        Permission::insert([
            ['name' => 'books.read', 'guard_name' => ''],
            ['name' => 'books.create', 'guard_name' => ''],
            ['name' => 'books.delete', 'guard_name' => ''],
        ]);

        Role::find(1)->permissions()->sync(Permission::all());
    }

    /**
     * @test
     */
    public function whenBearerTokenIsValidExpectAcceptedWithMessage()
    {
        $admin = factory(User::class)->create();
        $admin->roles()->attach(Role::find(1));

        $this->actingAs($admin, 'api')
            ->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.id')
            ->assertJsonHasPath('data.name')
            ->assertJsonHasPath('data.first_name')
            ->assertJsonHasPath('data.last_name')
            ->assertJsonHasPath('data.roles')
            ->assertJsonHasPath('data.permissions');
    }
}
