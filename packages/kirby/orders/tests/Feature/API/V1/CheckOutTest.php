<?php

namespace Orders\Tests\Feature\API\V1;

use App\Mail\OrderCreated;
use Illuminate\Support\Facades\Mail;
use Kirby\Orders\Models\Order;
use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use ProductsPackageSeed;
use Tests\TestCase;

class CheckOutTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/orders';

    /**
     * @var string
     */
    private $method = 'POST';

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductsPackageSeed::class);
    }

    /**
     * @test
     */
    public function shouldReturnOkWhenOrderIsAbleToCreate()
    {
        $products = Product::all();
        $orderData = [
            'products' => [
                ['requested_quantity' => 5, 'product' => ['id' => $products[0]->id]],
                ['requested_quantity' => 7, 'product' => ['id' => $products[1]->id]],
            ],
            'shipping' => [
                'price' => 4000,
                'address_street_type' => 'Carrera',
                'address_line_1' => '12',
                'address_line_2' => '2D',
                'address_line_3' => '29',
                'address_additional_info' => 'Edificio Villa Sofía, apartamento 301',
            ],
            'payment_method' => ['name' => 'cash'],
        ];

        Mail::fake();

        $this->actingAs($user = factory(User::class)->create(), 'api')
            ->json($this->method, $this->endpoint, $orderData)
            ->assertStatus(202);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'address' => 'Carrera 12 #2D - 29',
            'address_additional_info' => 'Edificio Villa Sofía, apartamento 301',
            'shipping_price' => 4000, // is a default value
        ]);

        $order = Order::first();

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $products[0]->id,
            'product_name' => $products[0]->name,
            'product_code' => $products[0]->code,
            'product_slug' => $products[0]->slug,
            'product_sm_image_url' => $products[0]->sm_image_url,
            'product_md_image_url' => $products[0]->md_image_url,
            'product_lg_image_url' => $products[0]->lg_image_url,
            'product_cost' => $products[0]->cost,
            'product_price' => $products[0]->price,
            'product_unity' => $products[0]->unity,
            'product_quantity' => $products[0]->quantity,
            'product_pum_unity' => $products[0]->pum_unity,
            'product_pum_price' => $products[0]->pum_price,
            'requested_quantity' => 5,
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $products[1]->id,
            'product_name' => $products[1]->name,
            'product_code' => $products[1]->code,
            'product_slug' => $products[1]->slug,
            'product_sm_image_url' => $products[1]->sm_image_url,
            'product_md_image_url' => $products[1]->md_image_url,
            'product_lg_image_url' => $products[1]->lg_image_url,
            'product_cost' => $products[1]->cost,
            'product_price' => $products[1]->price,
            'product_unity' => $products[1]->unity,
            'product_quantity' => $products[1]->quantity,
            'product_pum_unity' => $products[1]->pum_unity,
            'product_pum_price' => $products[1]->pum_price,
            'requested_quantity' => 7,
        ]);

        Mail::assertQueued(OrderCreated::class, function ($mail) use ($order, $user) {
            return $mail->order->id === $order->id &&
            $mail->hasTo($user->email) &&
            $mail->hasBcc(config('shop.email'));
        });
    }
}
