<?php


namespace carono\giix\templates\model;



use carono\codegen\ClassGenerator;

class QueryExtended extends ClassGenerator
{
    protected function phpDocComments()
    {
        $modelFullClassName = $this->params['modelClassName'];
        if ($this->generator->ns !== $this->generator->queryNs) {
            $modelFullClassName = '\\' . $this->generator->ns . '\\' . $modelFullClassName;
        }
        return [
            "This is the ActiveQuery class for $modelFullClassName",
            "@see $modelFullClassName"
        ];
    }
}