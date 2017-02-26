<?php

namespace WirksamesDesign\LaravelUuid\Database;

use Illuminate\Database\Eloquent\Model;
use WirksamesDesign\LaravelUuid\Database\Traits\UuidBinaryHelpersTrait;

class MySqlUuidSettingsHelper
{
    use UuidBinaryHelpersTrait;

    private $model;

    // Define some default Uuid settings which are applied to all models. If specific settings are defined on a
    // model in a static uuidSettings field, they will be merged on top of these defaults.
    private $defaultUuidSettings = [
      'detectColumns'    => true,
      'optimize'         => false,
      'version'          => 4,
      'generateOnInsert' => false,
    ];

    // Extra settings for the primary key field
    private $defaultUuidSettingsPrimaryKey = [
      'generateOnInsert' => true,
    ];

    private static $tableModelMap = [];

    public function __construct(Model $model = null) {
        $this->model = $model;
    }

    /**
     * This function checks whether a given column should be treated as a binary uuid column
     *
     * @param string $column The name of the column
     * @param null $value Optional value for the detection routine
     * @return bool
     */
    public function isBinaryUuidColumn($column, $value = null) {
        $isBinaryUuidColumn = false;

        $columnUuidSettings = $this->getUuidSettingsForColumn($column);

        // if the model's UUID settings contain an entry for this field,
        // the parser already inserted isUuidColumn => true into the settings.
        $isKnownUuidColumn = array_has(
            $columnUuidSettings,
            'isUuidColumn'
          ) && $columnUuidSettings['isUuidColumn'] === true;

        $shouldDetectColumns = array_has(
            $columnUuidSettings,
            'detectColumns'
          ) && $columnUuidSettings['detectColumns'] === true;

        $generateOnInsert = array_has(
            $columnUuidSettings,
            'generateOnInsert'
          ) && $columnUuidSettings['generateOnInsert'] === true;

        if ($isKnownUuidColumn ||
          $generateOnInsert || (
            $shouldDetectColumns
            && is_string($value)
            && strlen($value) == 36
            && self::isUuid($value))
        ) {
            $isBinaryUuidColumn = true;
        }

        return $isBinaryUuidColumn;
    }

    /**
     * Merges the default settings with the model's general and field-level settings.
     * If the model's UUID settings contain an entry for a column, isUuidColumn => true is inserted into the array.
     *
     * @param string $column The name of the column
     * @return array The applicable settings
     */
    public function getUuidSettingsForColumn($column) {
        $settings = [
          'defaults' => $this->defaultUuidSettings,
          'model'    => $this->defaultUuidSettings,
          'column'   => $this->defaultUuidSettings,
        ];

        $isPrimaryKey = false;

        list($table, $column) = $this->getTableFromColumn($column);

        $modelClass = $this->getModelFromTable($table);

        // Resolve the models settings by merging any custom model settings into the default settings
        if(gettype($modelClass) === 'string') {
            if(property_exists($modelClass, 'uuidSettings')) {
                $settings['model'] = array_merge($settings['defaults'], $modelClass::$uuidSettings);
            }
            // Check whether the field is the primary key
            $isPrimaryKey = $column === (new $modelClass)->getKeyName();
        };

        $settings = $this->mergeUuidColumnSettings($column, $settings, $isPrimaryKey);

        return $settings['column'];
    }


    /**
     * Returns the corresponding model for a given table name
     * @param $table
     * @return bool|string
     */
    public function getModelFromTable($table)
    {
        $modelClass = false;
        $pivotClass = 'Illuminate\Database\Eloquent\Relations\Pivot';

        if(false === is_null($this->model) && $this->model->getTable() === $table) {
            $modelClass = get_class($this->model);
        } elseif (array_key_exists($table, self::$tableModelMap)) {
            return self::$tableModelMap[$table];
        } else {
            // credits to Mauro Baptista (http://stackoverflow.com/a/41155142/1974291)
            foreach (get_declared_classes() as $class) {
                $isPivot = $class === $pivotClass || is_subclass_of($class, $pivotClass);

                if (!$isPivot && is_subclass_of($class, 'Illuminate\Database\Eloquent\Model')) {
                    $model = new $class;
                    if ($model->getTable() === $table) {
                        $modelClass = $class;
                        self::$tableModelMap[$table] = $modelClass;
                        break; // end the foreach loop
                    }
                }
            }
        }
        return $modelClass;
    }

    /**
     * @param $column
     * @param $settings
     * @param $columnIsPK
     * @return mixed
     */
    private function mergeUuidColumnSettings($column, $settings, $columnIsPK)
    {
        // initialize the column settings to the model settings and remove all settings for other columns
        $settings['column'] = array_except($settings['model'], ['columns']);

        // Check for specific column settings
        $columnHasIndividualSettings = array_has($settings['model'], "columns.$column");

        // add the default settings for PK columns
        if ($columnIsPK) {
            $settings['column'] = array_merge(
              $settings['column'],
              $this->defaultUuidSettingsPrimaryKey
            );
        }

        if ($columnHasIndividualSettings) {
            // Finally merge the specific settings for the field on top
            // We're removing the detectColumns setting in favor of a isUuidColumn setting
            // since we can assume it's a UUID column if it has settings.
            // Merging the column settings last also enables us to explicitly mark a field as isUuidColumn => false
            $settings['column'] = array_merge(
              array_except($settings['column'], ['detectColumns']),
              ['isUuidColumn' => true],
              $settings['model']['columns'][$column]
            );
        }

        return $settings;
    }

    /**
     * @param $column
     * @return array
     */
    public function getTableFromColumn($column)
    {
        $table = '';
        if (is_string($column) && true === str_contains($column, '.')) {
            list($table, $column) = explode('.', $column);
        } elseif (false === is_null($this->model)) {
            $table = $this->model->getTable();
            if(is_array($column)) {
                $column = key($column);
            }
        }
        return [$table, $column];
    }
}
