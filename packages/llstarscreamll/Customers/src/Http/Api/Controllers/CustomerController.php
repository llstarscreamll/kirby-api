<?php
namespace llstarscreamll\Customers\Http\Api\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Customers\Actions\PaginateCustomersAction;
use llstarscreamll\Customers\Http\Api\Resources\CustomerResource;

/**
 * Class CustomerController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request                     $request
     * @param  PaginateCustomersAction     $action
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, PaginateCustomersAction $action)
    {
        $paginatedCustomers = $action->run($request);

        return CustomerResource::collection($paginatedCustomers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request    $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request    $request
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
