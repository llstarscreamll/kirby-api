<?php
namespace Authentication\Helper;

use llstarscreamll\Users\Models\User;

/**
 * Class Api.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Api extends \Codeception\Module
{
    /**
     * Create and log in the admin user.
     *
     * @param  \llstarscreamll\Users\Models\User|null $user
     * @param  string                                 $driver
     * @return App\Containers\User\Models\User
     */
    public function amLoggedAsUser(User $user = null, string $driver = 'api'): User
    {
        if (is_array($user)) {
            $user = User::create($user);
        }

        if (is_null($user)) {
            $user = User::create([
                'name'     => 'admin user',
                'email'    => 'admin@admin.com',
                'password' => bcrypt('admin'),
            ]);
        }

        return $this->loginUser($user, $driver);
    }

    /**
     * Log in the given user on the given guard.
     *
     * @param  \llstarscreamll\Users\Models\User   $user
     * @param  string                              $driver
     * @return \llstarscreamll\Users\Models\User
     */
    public function loginUser(User $user, string $driver = 'api'): User
    {
        app('auth')->guard($driver)->setUser($user);
        app('auth')->shouldUse($driver);

        return $user;
    }
}
