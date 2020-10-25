<?php

return [
    'email' => explode(',', env('SHOP_EMAILS', 'crm@acme.com')),
    'default-product-image' => env('SHOP_DEFAULT_PRODUCT_IMAGE', 'https://thednetworks.com/wp-content/uploads/2012/01/picture_not_available_400-300.png'),
];
