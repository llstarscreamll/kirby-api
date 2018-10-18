<?php
namespace llstarscreamll\Authentication\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Lcobucci\JWT\Parser;
use llstarscreamll\Authentication\Http\Requests\LoginRequest;

/**
 * Class ApiAuthenticationController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ApiAuthenticationController extends Controller
{
    const AUTH_ROUTE = '/oauth/token';

    /**
     * @var array
     */
    private $baseData = [];

    public function __construct()
    {
        $this->baseData = [
            'client_id'     => config('authentication.clients.web.id'),
            'client_secret' => config('authentication.clients.web.secret'),
        ];
    }

    /**
     * @param LoginRequest $request
     */
    public function login(LoginRequest $request)
    {
        $data = $this->baseData + [
            'grant_type' => $request->grant_type ?? 'password',
            'scope'      => $request->scope ?? '',
            'username'   => $request->email,
            'password'   => $request->password,
        ];

        $response = $this->makeRequestToOAuthServer($data);

        if ($response['statusCode'] != '200') {
            return response($response['content'], $response['statusCode']);
        }

        $authTokenCookie = cookie(
            'accessToken',
            $response['content']['access_token'],
            config('auth.api.token-expires-in'),
            null,
            null,
            false,
            true
        );

        $refreshTokenCookie = cookie(
            'refreshToken',
            $response['content']['refresh_token'],
            config('auth.api.refresh-token-expires-in'),
            null,
            null,
            false,
            true
        );

        return response($response['content'], $response['statusCode'])
            ->withCookie($authTokenCookie)
            ->withCookie($refreshTokenCookie);
    }

    /**
     * @param Request $request
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
     * @param $data
     */
    private function makeRequestToOAuthServer($data)
    {
        $authFullApiUrl = config('app.url').self::AUTH_ROUTE;
        $headers        = ['HTTP_ACCEPT' => 'application/json'];
        $request        = Request::create($authFullApiUrl, 'POST', $data, [], [], $headers);
        $response       = App::handle($request);
        $content        = \GuzzleHttp\json_decode($response->getContent(), true);
        $statusCode     = $response->getStatusCode();

        return ['content' => $content, 'statusCode' => $statusCode];
    }
}
