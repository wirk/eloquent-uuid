<?php

namespace WirksamesDesign\LaravelUuid\Database\Query\Processors;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\MySqlProcessor as BaseMySqlProcessor;
use WirksamesDesign\LaravelUuid\Database\Traits\UuidBinaryHelpersTrait;


class MySqlProcessor extends BaseMySqlProcessor
{
    use UuidBinaryHelpersTrait;

    /**
     * Process the results of a "select" query.
     *
     * @param  Builder  $query
     * @param  array  $results
     * @return array
     */
    public function processSelect(Builder $query, $results)
    {
        foreach ($results as $record) {
            foreach ($record as $fieldName => $value) {
                if(self::isBinaryUuid($value)) {
                    $record->{$fieldName} = self::uuidBinaryToString($value);
                }
            }
        }
        return $results;
    }
}
