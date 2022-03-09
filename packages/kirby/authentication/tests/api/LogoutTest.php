<?php

namespace Kirby\Authentication\Tests\api;

use Kirby\Users\Models\User;

/**
 * Class LogoutTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class LogoutTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/auth/logout';

    /**
     * @var string
     */
    private $accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjI3NTJmZDc4ZDRkMGI5N2EzNDY1MjgzNTg0ODc5NzM0YzRjNDhiN2U4YTEzN2ZiOGM2NDM4NGFjZDk0ZTM2N2MzMTQzMTY4MGUwYjI4ODgxIn0.eyJhdWQiOiI0IiwianRpIjoiMjc1MmZkNzhkNGQwYjk3YTM0NjUyODM1ODQ4Nzk3MzRjNGM0OGI3ZThhMTM3ZmI4YzY0Mzg0YWNkOTRlMzY3YzMxNDMxNjgwZTBiMjg4ODEiLCJpYXQiOjE1Mzk1NTY4MDAsIm5iZiI6MTUzOTU1NjgwMCwiZXhwIjoxNTM5NjQzMjAwLCJzdWIiOiIyIiwic2NvcGVzIjpbXX0.Y03iCUM-ExgKXEQEPuhYzqQPwiaWi3JLIQjPuBySJZNq0ryPmE2uu0wgmqdqWVL3ac9ow_Hl8WG-Pa3On_NwfhlJljfW1vuq19Y-cf_HcjRxYyDaiQcwuliTcAZmipqm6RhL6BUn5XMcydivsOtDV11Xb9YoDxZgspOb6rExA2NZAm9ythsRq2OzTkUmskHQ7U_cNOM31eu3XsB7mK4isFr_SmjUlCjdtul2VHog2HMMU3y6ztuCymKutLnxCm124_4ib9HKyqZIWDEeq2E3jnIQYWoixlVxWLAgsAozIN0hkKs6Z1GzfM8dE6UKiX0wN4-oV7FcCPL3UQESo1KOhRxKR0NGc_Qh1yS_XI3fen-AuDQVcuPb0fOjH-gRQbMAU7lwCxVVdoFnrD12aNds49UX7OdpANM7Vk5JYltoU-wzxu5iyUsxXlSrKZGR6_9qHvFOp2awo-8aZJnmsstSDcZgrh4SPdG1ul-GAFgo-pIkYI9vak0DelyPbcfPeiovH4bVxZMsWni5Qk-bupLaOD95zi3kQ2V_dmtu3iV1wLkPSV2tvCCHc5WGaq0kEY-2r4cWVY5yr1ORshrbFdkpkPNOOQUL8w4kmFeOl5C4lC84CBjjqzvvTIIu9d0v9WJMCViuN4uKBQ9f9phkMMHb6_A1dBLmTVjbjIfQRoGkofo';

    /**
     * @test
     */
    public function whenBearerTokenIsValidExpectAcceptedWithMessage()
    {
        $this->actingAsAdmin(factory(User::class)->create())
            ->json('DELETE', $this->endpoint, [], ['Authorization' => 'Bearer '.$this->accessToken])
            ->assertStatus(202)
            ->assertJsonFragment(['message' => 'Token revoked successfully.']);
    }
}
