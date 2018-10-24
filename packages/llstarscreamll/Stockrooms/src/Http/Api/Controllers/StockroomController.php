<?php
namespace llstarscreamll\Stockrooms\Http\Api\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Stockrooms\Actions\PaginateStockroomsAction;
use llstarscreamll\Stockrooms\Http\Api\Resources\StockroomResource;

/**
 * Class StockroomController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class StockroomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request                     $request
     * @param  PaginateStockroomsAction    $action
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, PaginateStockroomsAction $action)
    {
        $paginatedStockrooms = $action->run($request);

        return StockroomResource::collection($paginatedStockrooms);
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
