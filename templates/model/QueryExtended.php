<?php


namespace carono\giix\templates\model;


use carono\giix\ClassGenerator;

class QueryExtended extends ClassGenerator
{
    public $skipIfExist = true;

    protected function formClassNamespace()
    {
        return $this->params['queryNs'];
    }

    protected function formOutputPath()
    {
        $alias = '@' . str_replace('\\', '/', $this->params['queryNs']);
        return \Yii::getAlias($alias) . '/' . $this->params['queryClassName'] . '.php';
    }

    protected function formExtends()
    {
        return "{$this->params['queryNs']}\base\\" . $this->params['queryClassName'];
    }

    protected function formClassName()
    {
        return $this->params['queryClassName'];
    }

    protected function phpDocComments()
    {
        $modelFullClassName = $this->params['className'];
        if ($this->generator->ns !== $this->generator->queryNs) {
            $modelFullClassName = '\\' . $this->generator->ns . '\\' . $modelFullClassName;
        }
        return [
            "This is the ActiveQuery class for $modelFullClassName",
            "@see $modelFullClassName"
        ];
    }
}