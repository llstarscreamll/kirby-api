<?php

namespace Kirby\Authentication\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Kirby\Authentication\Actions\WebLoginProxyAction;
use Kirby\Authentication\UI\API\V1\Requests\LoginRequest;
use Kirby\Authentication\UI\API\V1\Requests\SignUpRequest;
use Kirby\Users\UI\API\V1\Resources\UserResource;
use Lcobucci\JWT\Parser;

/**
 * Class ApiAuthenticationController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ApiAuthenticationController
{
    /**
     * @return \Illuminate\Http\Response
     */
    public function login(LoginRequest $request, WebLoginProxyAction $action)
    {
        $oAuthResponse = $action->run($request->email, $request->password);

        if ('200' != $oAuthResponse['statusCode']) {
            return response($oAuthResponse['content'], $oAuthResponse['statusCode']);
        }

        $authTokenCookie = cookie(
            'accessToken',
            $oAuthResponse['content']['access_token'],
            config('auth.api.token-expires-in'),
            null,
            null,
            false,
            true
        );

        $refreshTokenCookie = cookie(
            'refreshToken',
            $oAuthResponse['content']['refresh_token'],
            config('auth.api.refresh-token-expires-in'),
            null,
            null,
            false,
            true
        );

        return response($oAuthResponse['content'], $oAuthResponse['statusCode'])
            ->withCookie($authTokenCookie)
            ->withCookie($refreshTokenCookie);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $id = App::make(Parser::class)->parse($request->bearerToken())->getHeader('jti');

        DB::table('oauth_access_tokens')
            ->where('id', '=', $id)
            ->update(['revoked' => true]);

        $accessTokenCookie = Cookie::forget('accessToken');
        $refreshTokenCookie = Cookie::forget('refreshToken');

        return response(['message' => 'Token revoked successfully.'], 202)
            ->withCookie($accessTokenCookie)
            ->withCookie($refreshTokenCookie);
    }

    /**
     * @todo El código de este controlador está repetido, se debe abstraer
     *
     * @return \Illuminate\Http\Response
     */
    public function signUp(SignUpRequest $request, WebLoginProxyAction $action)
    {
        DB::table('users')->insert([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_prefix' => $request->phone_prefix,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $oAuthResponse = $action->run($request->email, $request->password);

        if ('200' != $oAuthResponse['statusCode']) {
            return response($oAuthResponse['content'], $oAuthResponse['statusCode']);
        }

        $authTokenCookie = cookie(
            'accessToken',
            $oAuthResponse['content']['access_token'],
            config('auth.api.token-expires-in'),
        );

        $refreshTokenCookie = cookie(
            'refreshToken',
            $oAuthResponse['content']['refresh_token'],
            config('auth.api.refresh-token-expires-in'),
        );

        return response($oAuthResponse['content'], $oAuthResponse['statusCode'])
            ->withCookie($authTokenCookie)
            ->withCookie($refreshTokenCookie);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function getAuthUser(Request $request)
    {
        $user = $request->user()->load([
            'roles:id,name',
            'roles.permissions:name',
            'permissions:id,name',
        ]);

        return new UserResource($user);
    }
}
