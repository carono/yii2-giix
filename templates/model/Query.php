<?php


namespace carono\giix\templates\model;


use carono\giix\ClassGenerator;
use Nette\PhpGenerator\Method;

class Query extends ClassGenerator
{
    protected function classUses()
    {
        return [
            'yii\db\ActiveQuery',
            'yii\data\Sort',
            'yii\data\ActiveDataProvider'
        ];
    }

    protected function formClassNamespace()
    {
        return "{$this->params['queryNs']}\base";
    }

    protected function formExtends()
    {
        return $this->params['queryBaseClass'];
    }

    protected function formClassName()
    {
        return $this->params['queryClassName'];
    }

    protected function phpDocComments()
    {
        return [
            "This is the ActiveQuery class for " . $this->params['modelFullClassName'],
            "@see " . $this->params['modelFullClassName']
        ];
    }

    /**
     * @param Method $method
     */
    public function all($method)
    {
        $modelFullClassName = $this->params['modelFullClassName'];
        $method->addParameter('db', null);
        $method->addBody('return parent::all($db);');
        $method->addComment('@inheritdoc');
        $method->addComment("@return {$modelFullClassName}[]");
    }

    /**
     * @param Method $method
     */
    public function one($method)
    {
        $modelFullClassName = $this->params['modelFullClassName'];
        $method->addParameter('db', null);
        $method->addBody('return parent::one($db);');
        $method->addComment('@inheritdoc');
        $method->addComment("@return {$modelFullClassName}");
    }

    /**
     * @param Method $method
     */
    public function search($method)
    {
        $method->addComment('@var mixed $filter');
        $method->addComment('@var array $options Options for ActiveDataProvider');
        $method->addParameter('filter', null);
        $method->addParameter('options', []);
        $method->addComment("@return ActiveDataProvider");
        $body = <<<PHP
\$query = clone \$this;
\$query->filter(\$filter);
\$sort = new Sort();
    return new ActiveDataProvider(
    array_merge([
        'query' => \$query,
        'sort'  => \$sort
    ], \$options)
);
PHP;
        $method->addBody($body);
    }

    /**
     * @param Method $method
     */
    public function filter($method)
    {
        $method->addComment('@var array|\yii\db\ActiveRecord $model');
        $method->addComment('@return $this');
        $method->addParameter('model', null);
        if (class_exists('carono\yii2helpers\QueryHelper')) {
            $this->phpNamespace->addUse('carono\yii2helpers\QueryHelper');
            $regular = <<<PHP
if (\$model instanceof \yii\db\ActiveRecord){
    QueryHelper::regular(\$model, \$this);
}
PHP;
            $method->addBody($regular);
        }
        $method->addBody('return $this;');
    }
}