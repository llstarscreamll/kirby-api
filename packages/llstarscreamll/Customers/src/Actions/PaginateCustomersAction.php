<?php
namespace llstarscreamll\Customers\Actions;

use Illuminate\Http\Request;
use llstarscreamll\Customers\Repositories\CustomerRepository;

/**
 * Class PaginateCustomersAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class PaginateCustomersAction
{
    /**
     * @var \llstarscreamll\Customers\Repositories\CustomerRepository
     */
    private $customerRepository;

    /**
     * @param \llstarscreamll\Customers\Repositories\CustomerRepository $customerRepository
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function run(Request $request)
    {
        return $this->customerRepository->paginate();
    }
}
