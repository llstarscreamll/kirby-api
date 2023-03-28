<?php

namespace Kirby\Novelties\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Kirby\Novelties\Contracts\NoveltyReportingRepository;
use Kirby\Novelties\DTOs\SearchEmployeeNoveltiesData;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Facades\Novelties;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Notifications\ExportNoveltiesResumeByTypeReady;
use Kirby\Users\Models\User;
use League\Csv\Writer;

/**
 * Class GenerateCsvEmployeeResumeByNoveltyTypeJob.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GenerateCsvEmployeeResumeByNoveltyTypeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public ?SearchEmployeeNoveltiesData $makeReportData = null;

    private Collection $noveltyTypes;

    /**
     * Create a new job instance.
     */
    public function __construct(SearchEmployeeNoveltiesData $makeReportData)
    {
        $this->makeReportData = $makeReportData;
        $this->noveltyTypes = new Collection();
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->loadNoveltyTypes()
            ->configureCustomSqlFunction()
            ->generateReport();

        return true;
    }

    private function loadNoveltyTypes()
    {
        $this->noveltyTypes = NoveltyType::where(['keep_in_report' => true])->get();

        return $this;
    }

    /**
     * Add MySQL CONCAT() support to Sqlite.
     */
    private function configureCustomSqlFunction(): self
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();
        $dbHandle = $connection->getPdo();

        // add MySQL concat() support to SQLite
        if ('sqlite' === $dbHandle->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            $dbHandle->sqliteCreateFunction('CONCAT', fn (...$input) => implode('', $input));
        }

        return $this;
    }

    private function generateReport()
    {
        $fileName = now()->format('Y-m-d_H_i_s').'.csv';
        $writer = Writer::createFromStream($file = tmpfile());
        $writer->setDelimiter(';');
        $writer->insertOne($this->getHeaders());

        app(NoveltyReportingRepository::class)->employeesResumeByNoveltyTypeChunk(
            $this->makeReportData,
            1000,
            fn ($chunk) => $writer->insertAll($chunk->map(fn ($report) => $report->toCsv(
                $this->getSortedAdditionNoveltyTypes()->pluck('id')->all(),
                $this->getSortedSubtractNoveltyTypes()->pluck('id')->all()
            ))->all())
        );

        Storage::disk('private')->put("novelties/exports/{$fileName}", $file);
        $fileUrl = Storage::disk('private')->url("novelties/exports/{$fileName}", now()->addMinutes(60));
        User::find($this->makeReportData->userId)->notify(new ExportNoveltiesResumeByTypeReady($fileUrl));
    }

    private function getHeaders(): array
    {
        return [
            // employee data
            'Código', '# de identificación', 'Nombres',
            // addition novelties
            ...$this->getSortedAdditionNoveltyTypes()->pluck('code'),
            // subtract novelties
            ...$this->getSortedSubtractNoveltyTypes()->pluck('code'),
            // addition novelties - subtract novelties
            'Total',
        ];
    }

    private function getSortedAdditionNoveltyTypes(): Collection
    {
        $balanceNoveltyTypeId = Novelties::defaultAdditionBalanceNoveltyTypeId();
        $additionNoveltyTypes = $this->noveltyTypes
            ->filter(fn ($noveltyType) => $noveltyType->id !== $balanceNoveltyTypeId)
            ->filter(fn ($noveltyType) => $noveltyType->operator->is(NoveltyTypeOperator::Addition()))
            ->sortBy('code');

        return collect([])
            ->push($this->noveltyTypes->firstWhere('id', $balanceNoveltyTypeId))
            ->concat($additionNoveltyTypes);
    }

    private function getSortedSubtractNoveltyTypes(): Collection
    {
        $balanceNoveltyTypeId = Novelties::defaultSubTractBalanceNoveltyTypeId();
        $subtractNoveltyTypes = $this->noveltyTypes
            ->filter(fn ($noveltyType) => $noveltyType->id !== $balanceNoveltyTypeId)
            ->filter(fn ($noveltyType) => $noveltyType->operator->is(NoveltyTypeOperator::Subtraction()))
            ->sortBy('code');

        return collect([])
            ->push($this->noveltyTypes->firstWhere('id', $balanceNoveltyTypeId))
            ->concat($subtractNoveltyTypes);
    }
}
