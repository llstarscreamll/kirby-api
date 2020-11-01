<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductImageProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $productCode;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $productCode)
    {
        $this->productCode = $productCode;
    }
}
