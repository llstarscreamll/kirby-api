<?php
namespace llstarscreamll\Authentication\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use llstarscreamll\Authentication\Actions\WebLoginProxyAction;
use llstarscreamll\Authentication\Http\Requests\LoginRequest;
use llstarscreamll\Authentication\Http\Requests\SignUpRequest;
use llstarscreamll\Users\UI\API\Resources\UserResource;

/**
 * Class ApiAuthenticationController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ApiAuthenticationController extends Controller
{
    /**
     * @param  \llstarscreamll\Authentication\Http\Requests\LoginRequest  $request
     * @param  \llstarscreamll\Authentication\Actions\WebLoginProxyAction $action
     * @return Illuminate\Http\Response
     */
    public function login(LoginRequest $request, WebLoginProxyAction $action)
    {
        $oAuthResponse = $action->run($request->email, $request->password);

        if ($oAuthResponse['statusCode'] != '200') {
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
     * @param  \Illuminate\Http\Request   $request
     * @return Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $id = App::make(Parser::class)->parse($request->bearerToken())->getHeader('jti');

        DB::table('oauth_access_tokens')
            ->where('id', '=', $id)
            ->update(['revoked' => true]);

        $accessTokenCookie  = Cookie::forget('accessToken');
        $refreshTokenCookie = Cookie::forget('refreshToken');

        return response(['message' => 'Token revoked successfully.'], 202)
            ->withCookie($accessTokenCookie)
            ->withCookie($refreshTokenCookie);
    }

    /**
     * @todo El código de este controlador está repetido, se debe abstraer
     * @param  \llstarscreamll\Authentication\Http\Requests\SignUpRequest $request
     * @param  \llstarscreamll\Authentication\Actions\WebLoginProxyAction $action
     * @return Illuminate\Http\Response
     */
    public function signUp(SignUpRequest $request, WebLoginProxyAction $action)
    {
        DB::table('users')->insert([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $oAuthResponse = $action->run($request->email, $request->password);

        if ($oAuthResponse['statusCode'] != '200') {
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
     * @param  \Illuminate\Http\Request   $request
     * @return Illuminate\Http\Response
     */
    public function getAuthUser(Request $request)
    {
        $user = $request->user()->load(['roles', 'permissions']);

        return (new UserResource($user));
    }
}
