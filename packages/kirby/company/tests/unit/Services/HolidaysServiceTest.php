<?php

namespace Kirby\Company\Tests\unit\Services;

use BlastCloud\Guzzler\UsesGuzzler;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Kirby\Company\Services\HolidaysService;

/**
 * Class HolidaysServiceTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class HolidaysServiceTest extends \Tests\TestCase
{
    use UsesGuzzler;

    /**
     * @var string
     */
    private $apiKey = 'some-key';

    /**
     * @var string
     */
    private $apiUrl = 'http://my-api.test/path';

    /**
     * Success Calendarific API response, taken from the official docs, read more here:
     * https://calendarific.com/api-documentation.
     *
     * @var string
     */
    private $successApiResponse = '
    {
        "meta": {
            "code": 200
        },
        "response": {
            "holidays": [
                {
                    "name": "Holiday one",
                    "description": "Test description",
                    "date": {
                        "iso": "2018-12-31",
                        "datetime": {
                            "year": 2018,
                            "month": 12,
                            "day": 31
                        }
                    },
                    "type": [
                        "Type of Observance goes here"
                    ]
                },
                {
                    "name": "March Equinox",
                    "description": null,
                    "date": {
                        "iso": "2019-03-20T16:58:32-05:00",
                        "datetime": {
                            "year": 2019,
                            "month": 3,
                            "day": 20,
                            "hour": 16,
                            "minute": 58,
                            "second": 32
                        },
                        "timezone": {
                            "offset": "-05:00",
                            "zoneabb": "COT",
                            "zoneoffset": -18000,
                            "zonedst": 0,
                            "zonetotaloffset": -18000
                        }
                    },
                    "type": [
                        "Season"
                    ],
                    "locations": "All",
                    "states": "All"
                }
            ]
        }
    }';

    public function setUp(): void
    {
        parent::setUp();

        config(['company.services.calendarific.url' => $this->apiUrl]);
        config(['company.services.calendarific.key' => $this->apiKey]);
    }

    public function dataProvider(): array
    {
        return [
            [
                'country' => 'ca',
                'year' => 2018,
                'apiResponse' => new Response(200, [], $this->successApiResponse),
                'expected' => [
                    ['date' => '2018-12-31', 'name' => 'Holiday one', 'description' => 'Test description'],
                    ['date' => '2019-03-20', 'name' => 'March Equinox', 'description' => null],
                ],
            ],
            [
                'country' => 'co',
                'year' => 2019,
                'apiResponse' => new Response(200, [], '{"meta":{"code":200},"response":{"holidays":[]}'),
                'expected' => [],
            ],
            [
                'country' => 'br',
                'year' => 2020,
                'apiResponse' => new Response(200, [], '{"meta":{"code":200},"wtf":{"wtf":[]}'),
                'expected' => [],
            ],
            [
                'country' => 'us',
                'year' => 2021,
                'apiResponse' => new RequestException('Something went wrong!!', new Request('GET', 'test')),
                'expected' => [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider
     *
     * @param  mixed  $country
     * @param  mixed  $year
     * @param  mixed  $apiResponse
     * @param  mixed  $expected
     */
    public function shouldMakeCorrectCallToApiAndReturnMapedData($country, $year, $apiResponse, $expected)
    {
        $this->guzzler->expects($this->once())
            ->get($this->apiUrl)
            ->withQuery(['api_key' => $this->apiKey, 'country' => $country, 'year' => $year])
            ->willRespond($apiResponse);

        $service = new HolidaysService($this->guzzler->getClient());
        $result = $service->get($country, $year);

        $this->assertEquals($expected, $result);
    }
}
