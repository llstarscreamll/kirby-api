<?php
namespace llstarscreamll\Sales\Http\Api\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Sales\Actions\CreateSaleAction;
use llstarscreamll\Sales\Http\Api\Requests\CreateSaleRequest;
use llstarscreamll\Sales\Http\Api\Resources\SaleResource;

/**
 * Class SaleController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SaleController extends Controller
{
    /**
     * Creates new SaleController instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \llstarscreamll\Sales\Http\Api\Requests\CreateSaleRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateSaleRequest $request, CreateSaleAction $action)
    {
        $sale = $action->run($request);

        return new SaleResource($sale);
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
