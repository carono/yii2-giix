<?php


namespace carono\giix\templates\model;


use carono\giix\ClassGenerator;

class ModelExtended extends ClassGenerator
{
    public $skipIfExist = true;

    protected function formOutputPath()
    {
        $className = $this->params['className'];
        return \Yii::getAlias('@' . str_replace('\\', '/', $this->params['ns'])) . '/' . $className . '.php';
    }

    protected function formExtends()
    {
        return $this->params['ns'] . '\base\\' . $this->params['className'];
    }

    protected function formClassNamespace()
    {
        return $this->params['ns'];
    }

    protected function formClassName()
    {
        return $this->params['className'];
    }

    protected function phpDocComments()
    {
        $tableName = $this->params['tableName'];
        return [
            "This is the model class for table \"$tableName\"."
        ];
    }
}