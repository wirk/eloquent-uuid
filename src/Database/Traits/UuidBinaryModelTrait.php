<?php

namespace WirksamesDesign\LaravelUuid\Database\Traits;

use WirksamesDesign\LaravelUuid\Database\MySqlUuidSettingsHelper;
use WirksamesDesign\LaravelUuid\Database\Query\Builder as QueryBuilder;

/*
 * This trait is to be used with the DB::statement('ALTER TABLE table_name ADD COLUMN id BINARY(16) PRIMARY KEY')
 *
 * @package WirksamesDesign\LaravelMySQLUuid
 * @author Alsofronie\Uuid by Alex Sofronie <alsofronie@gmail.com>,
 *         Alexis Hildebrandt <Alexis.Hildebrandt@wirksames-design.de>
 * @license MIT
 */
trait UuidBinaryModelTrait
{
    use UuidBinaryHelpersTrait;

    private $uuidSettingsHelper;

    public function getUuidSettingsHelper() {
        if(is_null($this->uuidSettingsHelper)) {
            $this->uuidSettingsHelper = new MySqlUuidSettingsHelper($this);
        }
        return $this->uuidSettingsHelper;
    }

    /**
     * This function is used internally by Eloquent models to test if the model has auto increment value
     * @returns bool Always false
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * This function overrides the default boot static method of Eloquent models. It hooks the creation event with a
     * simple closure to set incrementing = false if the PK field is a binary uuid column, thus telling the core
     * query builder to insert the data with the primary key which will be supplied by the new query builder.
     */
    public static function bootUuidBinaryModelTrait()
    {
        static::creating(function ($model) {
            $keyName = $model->getKeyName();
            if ($keyName && $model->getUuidSettingsHelper()->isBinaryUuidColumn($keyName)) {
                // This is necessary because on \Illuminate\Database\Eloquent\Model::performInsert
                // will not check for $this->getIncrementing() but directly for $this->incrementing
                $model->incrementing = false;
                $pkUuidSettings = $model->getUuidSettingsHelper()->getUuidSettingsForColumn($keyName);

                if (true === $pkUuidSettings['generateOnInsert'] && false === array_key_exists($keyName, $model->getAttributes())) {
                    $model->$keyName = \Webpatser\Uuid\Uuid::generate($pkUuidSettings['version'])->string;
                }
            }
        }, 0);
    }

    /**
     * Modified find static function to accept both string and binary versions of uuid
     * @param  mixed $id       The id (binary or hex string)
     * @param  array $columns  The columns to be returned (defaults to *)
     * @return mixed           The model or null
     */
    public function find($id, $columns = array('*'))
    {
        return parent::where($this->getKeyName(), '=', $id)->first($columns);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
          $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

}


