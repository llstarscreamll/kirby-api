<?php

namespace Kirby\Company\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kirby\Company\Contracts\HolidaysServiceInterface;

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

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiUrl = config('company.services.calendarific.url');
        $this->apiKey = config('company.services.calendarific.key');
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function get(string $countryCode, int $year): array
    {
        try {
            $apiResponse = $this->httpClient->get($this->apiUrl, [
                'query' => [
                    'api_key' => $this->apiKey,
                    'country' => $countryCode,
                    'year' => $year,
                ],
            ]);

            $apiData = json_decode($apiResponse->getBody()->getContents(), true);
        } catch (\Throwable $th) {
            $apiData = [];
        }

        return $this->mapApiResponse($apiData);
    }

    /**
     * @param  array  $responseData
     */
    private function mapApiResponse($responseData): array
    {
        $responseData = Arr::get($responseData, 'response.holidays', []);

        return (new Collection($responseData))
            ->map(function ($holiday) {
                $date = Arr::get($holiday, 'date.iso');
                $date = Carbon::parse($date);

                return [
                    'date' => $date->toDateString(),
                    'name' => Arr::get($holiday, 'name'),
                    'description' => Arr::get($holiday, 'description'),
                ];
            })->all();
    }
}
