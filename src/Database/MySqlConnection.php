<?php

namespace WirksamesDesign\LaravelUuid\Database;

use WirksamesDesign\LaravelUuid\Database\Query\Processors\MySqlProcessor;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\MySqlProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new MySqlProcessor;
    }
}
