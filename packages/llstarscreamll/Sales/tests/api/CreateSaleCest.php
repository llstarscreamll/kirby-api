<?php
namespace Sales;

use llstarscreamll\Items\Models\Item;
use llstarscreamll\Sales\Models\SaleStatus;
use llstarscreamll\Shippings\Models\Shipping;
use llstarscreamll\Users\Models\User;
use Sales\ApiTester;

/**
 * Class CreateSaleCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateSaleCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/sales';

    /**
     * @var \llstarscreamll\Sales\Models\SaleStatus
     */
    private $defaultSaleStatus;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $items;

    /**
     * @var \llstarscreamll\Shippings\Models\Shipping
     */
    private $shipping;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->defaultSaleStatus = factory(SaleStatus::class)->create(['default' => true]);
        $this->items             = factory(Item::class, 2)->create();
        $this->shipping          = factory(Shipping::class)->create();

        $this->user = $I->amLoggedAsUser(factory(User::class)->create());
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
    public function createSaleWithoutCustomerData(ApiTester $I)
    {
        $data = [
            'items' => [
                ['id' => $this->items[0]->id, 'quantity' => 2],
                ['id' => $this->items[1]->id, 'quantity' => 4],
            ],
        ];

        $I->sendPOST($this->endpoint, $data);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeRecord('sales', [
            'id'             => 1,
            'seller_id'      => $this->user->id,
            'status_id'      => $this->defaultSaleStatus->id,
            'shipping_to_id' => null,
            'customer_id'    => null,
        ]);

        $I->seeRecord('item_sale', [
            'sale_id'  => 1,
            'item_id'  => $this->items[0]->id,
            'quantity' => 2,
            'price'    => $this->items[0]->sale_price,
            'tax'      => $this->items[0]->tax->percentage,
        ]);

        $I->seeRecord('item_sale', [
            'sale_id'  => 1,
            'item_id'  => $this->items[1]->id,
            'quantity' => 4,
            'price'    => $this->items[1]->sale_price,
            'tax'      => $this->items[1]->tax->percentage,
        ]);
    }
}
