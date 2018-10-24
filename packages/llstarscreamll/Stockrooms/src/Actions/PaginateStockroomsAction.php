<?php
namespace llstarscreamll\Stockrooms\Actions;

use Illuminate\Http\Request;
use llstarscreamll\Stockrooms\Repositories\StockroomRepository;

/**
 * Class PaginateStockroomsAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class PaginateStockroomsAction
{
    /**
     * @var \llstarscreamll\Stockrooms\Repositories\StockroomRepository
     */
    private $stockroomRepository;

    /**
     * @param \llstarscreamll\Stockrooms\Repositories\StockroomRepository $stockroomRepository
     */
    public function __construct(StockroomRepository $stockroomRepository)
    {
        $this->StockroomRepository = $stockroomRepository;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function run(Request $request)
    {
        return $this->StockroomRepository->paginate();
    }
}
