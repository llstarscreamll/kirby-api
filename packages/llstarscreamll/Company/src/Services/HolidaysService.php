<?php
namespace llstarscreamll\Company\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use llstarscreamll\Company\Contracts\HolidaysServiceInterface;

/**
 * Class HolidaysService.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class HolidaysService implements HolidaysServiceInterface
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param \GuzzleHttp\Client $httpClient
     */
    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiUrl = config('company.services.holidays.url');
        $this->apiKey = config('company.services.holidays.key');
    }

    /**
     * @param  string  $countryCode
     * @param  int     $year
     * @return array
     */
    public function get(string $countryCode, int $year): array
    {
        $apiResponse = $this->httpClient->get($this->apiUrl, [
            'query' => [
                'api_key' => $this->apiKey,
                'country' => $countryCode,
                'year' => $year,
            ],
        ]);

        $apiData = json_decode($apiResponse->getBody()->getContents(), true);
        $holidays = Arr::get($apiData, 'response.holidays', []);

        return $holidays;
    }
}
