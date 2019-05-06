<?php

namespace Authentication\Helper;

use llstarscreamll\Users\Models\User;
use llstarscreamll\Authorization\Models\Permission;

/**
 * Class Api.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Api extends \Codeception\Module
{
    /**
     * Create user, assign all existing permissions to him and login the user.
     *
     * @param  string                              $guard
     * @return \llstarscreamll\Users\Models\User
     */
    public function amLoggedAsAdminUser(string $guard = 'api'): User
    {
        $adminId = $this->getModule('Laravel5')->haveRecord('users', [
            'first_name' => 'admin',
            'last_name' => '',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin-password'),
        ]);

        $adminUser = User::find($adminId);

        $adminUser->syncPermissions(Permission::all());

        return $this->amLoggedAsUser($adminUser, $guard);
    }

    /**
     * Log in user the given user, if no user provided, then create a newly
     * guest user without any permissions or roles.
     *
     * @param  \llstarscreamll\Users\Models\User|null $user
     * @param  string                                 $guard
     * @return App\Containers\User\Models\User
     */
    public function amLoggedAsUser(User $user = null, string $guard = 'api'): User
    {
        if (is_null($user)) {
            $userId = $this->getModule('Laravel5')->haveRecord('users', [
                'first_name' => 'guest user',
                'last_name' => '',
                'email' => 'guest@user.com',
                'password' => bcrypt('guest-user-password'),
            ]);

            $user = User::find($userId);
        }

        return $this->loginUser($user, $guard);
    }

    /**
     * Log in the given user on the given guard.
     *
     * @param  \llstarscreamll\Users\Models\User   $user
     * @param  string                              $guard
     * @return \llstarscreamll\Users\Models\User
     */
    public function loginUser(User $user, string $guard = 'api'): User
    {
        app('auth')->guard($guard)->setUser($user);
        app('auth')->shouldUse($guard);

        return $user;
    }
}
