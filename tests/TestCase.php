<?php

namespace Tests;

use Illuminate\Contracts\Support\Jsonable;
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

    /**
     * Cast a JSON string to a database compatible type. Éste método es tomado
     * de la versión 8 de Laravel:
     * https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Testing/Concerns/InteractsWithDatabase.php#L192.
     *
     * @param array|string $value
     *
     * @return \Illuminate\Database\Query\Expression
     */
    public function castAsJson($value)
    {
        if ($value instanceof Jsonable) {
            $value = $value->toJson();
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $value = DB::connection()->getPdo()->quote($value);

        return DB::raw("CAST({$value} AS JSON)");
    }
}
