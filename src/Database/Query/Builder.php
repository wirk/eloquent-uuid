<?php

namespace WirksamesDesign\LaravelUuid\Database\Query;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use WirksamesDesign\LaravelUuid\Database\MySqlUuidSettingsHelper;
use WirksamesDesign\LaravelUuid\Database\Traits\UuidBinaryHelpersTrait;

class Builder extends \Illuminate\Database\Query\Builder {
    use UuidBinaryHelpersTrait;

    private $uuidSettingsHelper;

    /**
     * Create a new query builder instance.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Illuminate\Database\Query\Grammars\Grammar  $grammar
     * @param  \Illuminate\Database\Query\Processors\Processor  $processor
     * @return void
     */
    public function __construct(ConnectionInterface $connection, Grammar $grammar = null, Processor $processor = null)
    {
        $this->uuidSettingsHelper = new MySqlUuidSettingsHelper();
        parent::__construct($connection, $grammar, $processor);
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        $values = self::toQueryFormat($this->generatePrimaryKey($values));
        return parent::insert($values);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // if this function is only passed 2 parameters, the value will actually be in the $operator parameter
        $valueVariable = 'value';
        if (func_num_args() == 2) {
            $valueVariable = 'operator';
        }

        $$valueVariable = self::toQueryFormat($$valueVariable, $column);

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @param  bool    $not
     * @return \Illuminate\Database\Query\Builder
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if (is_array($values) || $values instanceof Arrayable) {
            $values = $this->toQueryFormat($values, $column);
        }
        return parent::whereIn($column, $values, $boolean, $not);
    }

    /**
     * Execute a query for a single record by ID.
     * Just like the core find method in the query builder,
     * this method assumes the private key is stored in the column "id".
     *
     * @param int $id
     * @param array $columns
     * @return \Illuminate\Database\Query\Builder
     */
    public function find($id, $columns = ['*'])
    {
        $id = $this->toQueryFormat($id, 'id');
        return parent::find($id, $columns);
    }

    /**
     * Recursively transforms the relevant fields in a list of records,
     * a single record or a single value to the binary format.
     *
     * @param $value
     * @param string $column
     * @return array|string
     */
    private function toQueryFormat($value, $column = '') {
        if(is_array($value)) {
            foreach ($value as $itemKey => $itemValue) {
                // If an array of values with string indexes was passed, we assume that it is a list of
                // columns with their respective values. If a numbered array was passed, we assume it is a list
                // of values, all related to a single column.
                if(false === is_numeric($itemKey)) {
                    $column = $itemKey;
                }
                $value[$itemKey] = self::toQueryFormat($itemValue, $column);
            }
        } elseif (is_string($value)) {
            if (strlen($column) && false === str_contains($column, '.')){
                $column = $this->from . '.' . $column;
            }
            if ($this->uuidSettingsHelper->isBinaryUuidColumn($column, $value)) {
                $transformSettings = $this->uuidSettingsHelper->getUuidSettingsForColumn($column);
                $value = $this->uuidStringToBinary($value, $transformSettings['optimize']);
            }
        }
        return $value;
    }

    /**
     * Inserts a value for the primary key into an array of values
     * @param array $values
     * @return array
     */
    public function generatePrimaryKey(array $values)
    {
        $keyName = 'id';
        if ($modelClass = $this->uuidSettingsHelper->getModelFromTable($this->from)) {
            $keyName = (new $modelClass)->getKeyName();
        }
        $qualifiedKeyName = $this->from . '.' . $keyName;
        if ($this->uuidSettingsHelper->isBinaryUuidColumn($qualifiedKeyName)) {
            $pkUuidSettings = $this->uuidSettingsHelper->getUuidSettingsForColumn($qualifiedKeyName);

            // generate the uuid for the new record's primary key if it's not deactivated for this model and
            // nobody else has generated a primary key yet.
            if (true === $pkUuidSettings['generateOnInsert'] && false === array_key_exists($keyName, $values)) {
                $values[$keyName] = \Webpatser\Uuid\Uuid::generate($pkUuidSettings['version'])->string;
            }
        }
        return $values;
    }
}