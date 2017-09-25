<?php


namespace carono\giix\templates\model;

use carono\codegen\ClassGenerator;

class ModelExtended extends ClassGenerator
{
    protected function phpDocComments()
    {
        $tableName = $this->params['tableName'];
        return [
            "This is the model class for table \"$tableName\"."
        ];
    }
}