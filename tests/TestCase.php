<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Kirby\Authorization\Models\Permission;
use Kirby\Authorization\Models\Role;
use Kirby\Users\Models\User;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use CreateTestResponse;
    use RefreshDatabase;

    /**
     * {@inheritdoc}
     */
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        $files = $this->extractFilesFromDataArray($data);

        $content = json_encode($data);

        $parameters = 'GET' === $method ? $data : [];

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        return $this->call(
            $method,
            $uri,
            $parameters,
            $this->prepareCookiesForJsonRequest(),
            $files,
            $this->transformHeadersToServerVars($headers),
            $content
        );
    }

    public function haveRecord(string $table, array $data)
    {
        DB::table($table)->insert($data);

        return $this;
    }

    /**
     * @param User   $user
     * @param string $driver
     *
     * @return $this
     */
    public function actingAsAdmin(User $user = null, $driver = 'api')
    {
        $user = $user ?? factory(User::class)->create();
        $user->syncPermissions(Permission::all());
        $user->syncRoles(Role::all());

        return $this->be($user, $driver);
    }

    /**
     * @param User   $user
     * @param string $driver
     *
     * @return $this
     */
    public function actingAsGuest(User $user = null, $driver = 'api')
    {
        $user = $user ?? factory(User::class)->create();

        return $this->be($user, $driver);
    }

    /**
     * @param null|string $connection
     *
     * @return mixed
     */
    public function assertDatabaseRecordsCount(int $count, string $table, array $data = [], $connection = null)
    {
        $this->assertThat(
            $table,
            new MatchCountInDatabase($this->getConnection($connection), $data, $count)
        );

        return $this;
    }
}
