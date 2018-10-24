<?php
namespace llstarscreamll\Shippings\Http\Api\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Shippings\Actions\PaginateShippingsAction;
use llstarscreamll\Shippings\Http\Api\Resources\ShippingResource;

/**
 * Class ShippingController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ShippingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request                     $request
     * @param  PaginateShippingsAction     $action
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, PaginateShippingsAction $action)
    {
        $paginatedShippings = $action->run($request);

        return ShippingResource::collection($paginatedShippings);
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
