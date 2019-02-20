<?php
namespace Authentication;

use Authentication\ApiTester;

/**
 * Class LogoutCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogoutCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/logout';

    /**
     * @var string
     */
    private $accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjI3NTJmZDc4ZDRkMGI5N2EzNDY1MjgzNTg0ODc5NzM0YzRjNDhiN2U4YTEzN2ZiOGM2NDM4NGFjZDk0ZTM2N2MzMTQzMTY4MGUwYjI4ODgxIn0.eyJhdWQiOiI0IiwianRpIjoiMjc1MmZkNzhkNGQwYjk3YTM0NjUyODM1ODQ4Nzk3MzRjNGM0OGI3ZThhMTM3ZmI4YzY0Mzg0YWNkOTRlMzY3YzMxNDMxNjgwZTBiMjg4ODEiLCJpYXQiOjE1Mzk1NTY4MDAsIm5iZiI6MTUzOTU1NjgwMCwiZXhwIjoxNTM5NjQzMjAwLCJzdWIiOiIyIiwic2NvcGVzIjpbXX0.Y03iCUM-ExgKXEQEPuhYzqQPwiaWi3JLIQjPuBySJZNq0ryPmE2uu0wgmqdqWVL3ac9ow_Hl8WG-Pa3On_NwfhlJljfW1vuq19Y-cf_HcjRxYyDaiQcwuliTcAZmipqm6RhL6BUn5XMcydivsOtDV11Xb9YoDxZgspOb6rExA2NZAm9ythsRq2OzTkUmskHQ7U_cNOM31eu3XsB7mK4isFr_SmjUlCjdtul2VHog2HMMU3y6ztuCymKutLnxCm124_4ib9HKyqZIWDEeq2E3jnIQYWoixlVxWLAgsAozIN0hkKs6Z1GzfM8dE6UKiX0wN4-oV7FcCPL3UQESo1KOhRxKR0NGc_Qh1yS_XI3fen-AuDQVcuPb0fOjH-gRQbMAU7lwCxVVdoFnrD12aNds49UX7OdpANM7Vk5JYltoU-wzxu5iyUsxXlSrKZGR6_9qHvFOp2awo-8aZJnmsstSDcZgrh4SPdG1ul-GAFgo-pIkYI9vak0DelyPbcfPeiovH4bVxZMsWni5Qk-bupLaOD95zi3kQ2V_dmtu3iV1wLkPSV2tvCCHc5WGaq0kEY-2r4cWVY5yr1ORshrbFdkpkPNOOQUL8w4kmFeOl5C4lC84CBjjqzvvTIIu9d0v9WJMCViuN4uKBQ9f9phkMMHb6_A1dBLmTVjbjIfQRoGkofo';

    /**
     * @var string
     */
    private $refreshToken = 'def50200579eb4cec9a2860ca8adb3a41a6fdc17dffbb52ec3c0a5fe9babf0a038b47382c2a7ad9745d19a751b86b6bcb4dc0c9fc663d55f5f1050295f983de08afbb41d3563aaafb0d67261fd3d491833dc80e217c5cf31cc8887fceed0e83d82250ec99e92f5f9d5a84aa32d3dd792fd048969ec8d4afbab28d51d80e5f02bcff7c2690376fc34611c15d2822d3a078b8ca95214689f75d60cf5e3a0fd814c224e502da21143f4cde577300e83e015c8b90c7c2f60538e4c301292b232764f3a173738efe0bd2af2fb61d8e115d56e35890cbfa1ad29349c3ca87400822e2028bdcfd21416bf99cb1dbbeaa40306d002a8eeba62aefdbfa1b322d89de48752f794bef7647f5dfcd1e95ae4b398706c12d75f042efb3348ce7d139cfecd0a932e7afcbc923e2cc24d0e0e3a30f6fd5773e5b0be49cfe153bbba9b37b289f2bc7dda0a195b8e58de27107f17d3bcf53a42792fc8bd0b2daa50d38be28e5c6c2812';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @param ApiTester $I
     */
    public function _after(ApiTester $I) {}

    /**
     * @test
     * @param ApiTester $I
     */
    public function logoutSuccessfully(ApiTester $I)
    {
        $I->amLoggedAsUser();
        $I->haveHttpHeader('Authorization', 'Bearer '.$this->accessToken);

        $I->sendDELETE($this->endpoint);

        $I->seeResponseCodeIs(202);
        $I->seeResponseContainsJson(['message' => 'Token revoked successfully.']);
    }
}
