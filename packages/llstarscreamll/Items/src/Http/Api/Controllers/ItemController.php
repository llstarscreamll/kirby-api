<?php
namespace llstarscreamll\Items\Http\Api\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Items\Actions\PaginateItemsAction;
use llstarscreamll\Items\Http\Api\Resources\ItemResource;

/**
 * Class ItemController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request                     $request
     * @param  PaginateItemsAction         $action
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, PaginateItemsAction $action)
    {
        $paginatedItems = $action->run($request);

        return ItemResource::collection($paginatedItems);
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
