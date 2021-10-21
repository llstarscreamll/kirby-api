<?php

namespace Kirby\Authorization\Tests\Feature\CLI;

use AuthorizationPackageSeeder;
use Kirby\Authorization\Models\Permission;
use Kirby\Authorization\Models\Role;
use Tests\TestCase;

/**
 * Class RefreshAdminPermissionsCommandTest.
 *
 * Set de pruebas de la funcionalidad de sincronizar los permisos al rol
 * administrador del sistema.
 *
 * @package Kirby\Authorization\Tests
 */
class RefreshAdminPermissionsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthorizationPackageSeeder::class);
    }

    /**
     * Debe asociar todos los permisos existentes al rol administrador.
     *
     * @test
     */
    public function shouldAttachAllPermissionsToAdminRole()
    {
        Permission::insert([
            ['name' => 'books.read', 'guard_name' => ''],
            ['name' => 'books.create', 'guard_name' => ''],
            ['name' => 'books.delete', 'guard_name' => ''],
        ]);

        $this->assertEmpty(Role::find(1)->permissions);

        $this->artisan('authorization:refresh-admin-permissions')->assertExitCode(0);

        $this->assertCount(3, Role::find(1)->permissions);
    }
}
