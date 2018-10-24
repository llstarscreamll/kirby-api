<?php
namespace llstarscreamll\Items\Actions;

use Illuminate\Http\Request;
use llstarscreamll\Items\Repositories\ItemRepository;

/**
 * Class PaginateItemsAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class PaginateItemsAction
{
    /**
     * @var \llstarscreamll\Items\Repositories\ItemRepository
     */
    private $itemRepository;

    /**
     * @param \llstarscreamll\Items\Repositories\ItemRepository $itemRepository
     */
    public function __construct(ItemRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function run(Request $request)
    {
        return $this->itemRepository->paginate();
    }
}
