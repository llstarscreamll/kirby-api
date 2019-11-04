<?php

namespace Kirby\Authentication\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Class WebLoginProxyAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WebLoginProxyAction
{
    const AUTH_ROUTE = '/oauth/token';

    /**
     * @var array
     */
    private $baseData = [];

    public function __construct()
    {
        $this->baseData = [
            'client_id' => config('authentication.clients.web.id'),
            'client_secret' => config('authentication.clients.web.secret'),
        ];
    }

    /**
     * @param string $email
     * @param string $password
     */
    public function run(string $email, string $password): array
    {
        $requestPayload = $this->baseData + [
            'grant_type' => 'password',
            'scope' => '',
            'username' => $email,
            'password' => $password,
        ];

        $authFullApiUrl = config('app.url').self::AUTH_ROUTE;
        $headers = ['HTTP_ACCEPT' => 'application/json'];
        $request = Request::create($authFullApiUrl, 'POST', $requestPayload, [], [], $headers);
        $response = App::handle($request);

        $content = \GuzzleHttp\json_decode($response->getContent(), true);
        $statusCode = $response->getStatusCode();

        return ['content' => $content, 'statusCode' => $statusCode];
    }
}
