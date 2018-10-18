<?php
namespace Authentication\Helper;

use App\User;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    /**
     * Create and log in the admin user.
     *
     * @return App\Containers\User\Models\User
     */
    public function amLoggedAsUser(array $attrs = [], string $driver = 'api')
    {
        $user = User::firstOrcreate($attrs + [
            'cai_id'   => 'admin_cai_id',
            'name'     => 'admin',
            'email'    => 'admin@admin.com',
            'password' => bcrypt('admin'),
        ]);

        return $this->loginUser($user, $driver);
    }

    /**
     * Log in the given user.
     *
     * @param  UserModel                         $user
     * @return App\Containers\User\Models\User
     */
    public function loginUser(User $user, string $driver = 'api')
    {
        app('auth')->guard($driver)->setUser($user);
        app('auth')->shouldUse($driver);

        return $user;
    }
}
