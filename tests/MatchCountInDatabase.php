<?php

namespace Tests;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;

class MatchCountInDatabase extends Constraint
{
    /**
     * Number of records that will be shown in the console in case of failure.
     *
     * @var int
     */
    protected $show = 3;

    /**
     * The database connection.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The data that will be used to narrow the search in the database table.
     *
     * @var array
     */
    protected $data;

    /**
     * The expected data count from database query.
     *
     * @var int
     */
    protected $expectedCount;

    /**
     * Create a new constraint instance.
     */
    public function __construct(Connection $database, array $data, int $expectedCount)
    {
        $this->data = $data;
        $this->database = $database;
        $this->expectedCount = $expectedCount;
    }

    /**
     * Check if the data is found in the given table.
     *
     * @param  string  $table
     */
    public function matches($table): bool
    {
        return $this->database->table($table)->where($this->data)->count() === $this->expectedCount;
    }

    /**
     * Get the description of the failure.
     *
     * @param  string  $table
     */
    public function failureDescription($table): string
    {
        return sprintf(
            "data in the table [%s] matches [%s] rows with the attributes %s.\n\n%s",
            $table,
            $this->expectedCount,
            $this->toString(JSON_PRETTY_PRINT),
            $this->getAdditionalInfo($table)
        );
    }

    /**
     * Get a string representation of the object.
     *
     * @param  int  $options
     */
    public function toString($options = 0): string
    {
        return json_encode($this->data, $options);
    }

    /**
     * Get additional info about the records found in the database table.
     *
     * @param  string  $table
     * @return string
     */
    protected function getAdditionalInfo($table)
    {
        $query = $this->database->table($table);

        $similarResults = $query->where(
            array_key_first($this->data),
            $this->data[array_key_first($this->data)]
        )->limit($this->show)->get();

        if ($similarResults->isNotEmpty()) {
            $description = 'Found similar results: '.json_encode($similarResults, JSON_PRETTY_PRINT);
        } else {
            $query = $this->database->table($table);

            $results = $query->limit($this->show)->get();

            if ($results->isEmpty()) {
                return 'The table is empty.';
            }

            $description = 'Found: '.json_encode($results, JSON_PRETTY_PRINT);
        }

        if ($query->count() > $this->show) {
            $description .= sprintf(' and %s others', $query->count() - $this->show);
        }

        return $description;
    }
}
