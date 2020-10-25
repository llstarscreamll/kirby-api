<?php

namespace Kirby\Orders\UI\API\V1\Controllers;

use App\Mail\OrderCreated;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Kirby\Orders\Contracts\OrderRepository;
use Kirby\Orders\Models\Order;
use Kirby\Orders\UI\API\V1\Requests\StoreOrderRequest;
use Kirby\Products\Contracts\ProductRepository;
use Symfony\Component\HttpFoundation\Response;

class OrdersController
{
    /**
     * @param StoreOrderRequest $request
     * @param OrderRepository   $orderRepo
     */
    public function store(StoreOrderRequest $request, OrderRepository $orderRepo, ProductRepository $productRepo)
    {
        $now = now();
        $orderData = $request->validated();
        $shipping = $orderData['shipping'];

        DB::beginTransaction();

        $order = $orderRepo->create([
            'user_id' => $request->user()->id,
            'payment_method' => Arr::get($orderData, 'payment_method.name', 'cash'),
            'address' => "{$shipping['address_street_type']} {$shipping['address_line_1']} #{$shipping['address_line_2']} - {$shipping['address_line_3']}",
            'address_additional_info' => Arr::get($orderData, 'shipping.address_additional_info'),
            'shipping_price' => 4000,
        ]);

        $products = $productRepo->whereIn('id', data_get($orderData, 'products.*.product.id'))->get();
        $orderProducts = array_map(fn ($cartItem) => [
            'order_id' => $order->id,
            'product_id' => ($product = $products->firstWhere('id', $cartItem['product']['id']))->id,
            'product_name' => $product->name,
            'product_code' => $product->code,
            'product_slug' => $product->slug,
            'product_sm_image_url' => $product->sm_image_url,
            'product_md_image_url' => $product->md_image_url,
            'product_lg_image_url' => $product->lg_image_url,
            'product_cost' => $product->cost,
            'product_price' => $product->price,
            'product_unity' => $product->unity,
            'product_quantity' => $product->quantity,
            'product_pum_unity' => $product->pum_unity,
            'product_pum_price' => $product->pum_price,
            'requested_quantity' => $cartItem['requested_quantity'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $orderData['products']);

        DB::table('order_products')->insert($orderProducts);

        DB::commit();

        Mail::to($request->user()->email)->bcc(config('shop.email'))->send(new OrderCreated($order));

        return response(['data' => 'ok'], Response::HTTP_ACCEPTED);
    }
}
