<?php

namespace Kirby\Production\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductionReportRepository
{
    public function getKilogramsAcummulatedByProduct();
}
