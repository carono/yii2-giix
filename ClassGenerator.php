<?php


namespace carono\giix;


class ClassGenerator extends \carono\codegen\ClassGenerator
{
    public $skipIfExist = false;
    public $depends = [];
}