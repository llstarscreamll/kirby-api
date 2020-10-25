@component('mail::message')
# Hola {{ $order->user->first_name }}!

Tu orden en {{ config('app.name') }} ha sido recibida:

<table style="box-sizing:border-box; margin:30px auto; width:100%">
    <thead style="font-size: 13px; box-sizing:border-box">
        <tr>
            <th style="box-sizing:border-box; border-bottom:1px solid #edeff2; padding-bottom:8px; margin:0; width: 250px;">Producto</th>
            <th style="box-sizing:border-box; border-bottom:1px solid #edeff2; padding-bottom:8px; margin:0">Cant</th>
            <th style="box-sizing:border-box; border-bottom:1px solid #edeff2; padding-bottom:8px; margin:0">P. unitario</th>
            <th style="box-sizing:border-box; border-bottom:1px solid #edeff2; padding-bottom:8px; margin:0">Total</th>
        </tr>
    </thead>
    <tbody style="font-size: 12px; box-sizing:border-box">
    @foreach ($order->products as $product)
        <tr>
            <td style="box-sizing:border-box; color:#74787e; line-height:18px; padding:10px 0; margin:0">{{ $product->product_name }}</td>
            <td style="box-sizing:border-box; color:#74787e; line-height:18px; padding:10px 0; margin:0" align="right">{{ $product->requested_quantity }}</td>
            <td style="box-sizing:border-box; color:#74787e; line-height:18px; padding:10px 0; margin:0" align="right">{{ $product->productPriceFormatted() }}</td>
            <td style="box-sizing:border-box; color:#74787e; line-height:18px; padding:10px 0; margin:0" align="right">{{ $product->totalFormatted() }}</td>
        </tr>
    @endforeach
        <tr>
            <td style="padding: 2px 0;" colspan="3" align="right">Envío:</td>
            <td style="padding: 2px 0;" align="right">{{ $order->shippingPriceFormatted() }}</td>
        <tr>
        <tr>
            <td style="box-sizing:border-box; color:#74787e; line-height:18px; padding:2px 0; margin:0; font-weight: 700;" align="right" colspan="3">Total a Pagar:</td>
            <td style="box-sizing:border-box; color:#74787e; line-height:18px; padding:2px 0; margin:0; font-weight: 700;" align="right">{{ $order->totalFormatted() }}</td>
        <tr>
    </tbody>
</table>

<table style="font-size: 13px; box-sizing:border-box; margin:30px auto; width:100%">
    <tbody>
        <tr style="padding: 4px 0;">
            <td style="width: 150px; padding: 4px 0; font-weight: 700;" align="right">Método de pago:</td>
            <td style="padding: 0 10px;">Efectivo</td>
        </tr>
        <tr style="padding: 4px 0;">
            <td style="width: 150px; padding: 4px 0; font-weight: 700;"  align="right">Dirección de entrega:</td>
            <td style="padding: 0 10px;">{{ $order->address }} {{ $order->address_additional_info }}</td>
        </tr>
        <tr style="padding: 4px 0;">
            <td style="width: 150px; padding: 4px 0; font-weight: 700;"  align="right">Recibe:</td>
            <td style="padding: 0 10px;">{{ $order->user->name }}</td>
        </tr>
        <tr style="padding: 4px 0;">
            <td style="width: 150px; padding: 4px 0; font-weight: 700;"  align="right">Teléfono:</td>
            <td style="padding: 0 10px;">{{ $order->user->full_phone }}</td>
        </tr>
    </tbody>
</table>

Gracias por preferinos,<br>
{{ config('app.name') }}
@endcomponent
