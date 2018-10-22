<?php
namespace llstarscreamll\Sales\Actions;

use Carbon\Carbon;
use llstarscreamll\Items\Repositories\ItemRepository;
use llstarscreamll\Sales\Http\Api\Requests\CreateSaleRequest;
use llstarscreamll\Sales\Repositories\SaleRepository;
use llstarscreamll\Sales\Repositories\SaleStatusRepository;

/**
 * Class CreateSaleAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateSaleAction
{
    /**
     * @var \llstarscreamll\Sales\Repositories\SaleRepository
     */
    private $saleRepository;

    /**
     * @var \llstarscreamll\Items\Repositories\ItemRepository
     */
    private $itemRepository;

    /**
     * @var \llstarscreamll\Sales\Models\SaleStatus
     */
    private $defaultSaleStatus;

    /**
     * @param \llstarscreamll\Sales\Repositories\SaleRepository       $saleRepository
     * @param \llstarscreamll\Sales\Repositories\SaleStatusRepository $saleStatusRepository
     */
    public function __construct(
        ItemRepository       $itemRepository,
        SaleRepository       $saleRepository,
        SaleStatusRepository $saleStatusRepository
    ) {
        $this->itemRepository    = $itemRepository;
        $this->saleRepository    = $saleRepository;
        $this->defaultSaleStatus = $saleStatusRepository->getDefault();
    }

    /**
     * @param \llstarscreamll\Sales\Http\Api\Requests\CreateSaleRequest $request
     */
    public function run(CreateSaleRequest $request)
    {
        $data = $request->validated() + [
            'seller_id'  => $request->user()->id,
            'status_id'  => $this->defaultSaleStatus->id,
            'issue_date' => Carbon::now(),
        ];

        $sale = $this->saleRepository->create($data);

        $itemsData = collect($data['items'])->keyBy('id')->all();
        $itemsIds  = array_keys($itemsData);
        $items     = $this->itemRepository
                          ->with(['tax'])
                          ->findWhereIn('id', $itemsIds, ['id', 'sale_price', 'tax_id'])
                          ->mapWithKeys(function ($item) use ($itemsData) {
                              return [$item->id => [
                                  'quantity' => array_get($itemsData, "{$item->id}.quantity", 1),
                                  'price'    => $item->sale_price,
                                  'tax'      => $item->tax->percentage,
                              ]];
                          })->all();

        $sale->items()->sync($items);

        return $sale;
    }
}
