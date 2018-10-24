<?php
namespace llstarscreamll\Shippings\Actions;

use Illuminate\Http\Request;
use llstarscreamll\Shippings\Repositories\ShippingRepository;

/**
 * Class PaginateShippingsAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class PaginateShippingsAction
{
    /**
     * @var \llstarscreamll\Shippings\Repositories\ShippingRepository
     */
    private $shippingRepository;

    /**
     * @param \llstarscreamll\Shippings\Repositories\ShippingRepository $shippingRepository
     */
    public function __construct(ShippingRepository $shippingRepository)
    {
        $this->shippingRepository = $shippingRepository;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function run(Request $request)
    {
        return $this->shippingRepository->paginate();
    }
}
