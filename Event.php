<?php


namespace carono\giix;


use carono\codegen\ClassGenerator;

class Event extends \yii\base\Event
{
    /**
     * @var ClassGenerator
     */
    public $class;
    /**
     * @var array
     */
    public $params;
    public $filePath;
    public $render;
}