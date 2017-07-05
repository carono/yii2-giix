<?php
use Nette\PhpGenerator\PhpNamespace;

/**
 * This is the template for generating the ActiveQuery class.
 */

/**
 * @var $this yii\web\View
 * @var $generator yii\gii\generators\model\Generator
 * @var $className string class name
 * @var $modelClassName string related model class name
 */
$modelFullClassName = $modelClassName;
if ($generator->ns !== $generator->queryNs) {
    $modelFullClassName = '\\' . $generator->ns . '\\' . $modelFullClassName;
}

$namespace = new PhpNamespace("{$generator->ns}\base");
$namespace->addUse('yii\data\Sort');
$namespace->addUse('yii\data\ActiveDataProvider');

$class = $namespace->addClass($className);
$class->addComment("This is the ActiveQuery class for $modelFullClassName");
$class->addComment("@see $modelFullClassName");


$class->addExtend(ltrim($generator->queryBaseClass));

$method = $class->addMethod('all');
$method->addParameter('db', null);
$method->addBody('return parent::all($db);');
$method->addComment('@inheritdoc');
$method->addComment("@return {$modelFullClassName}[]");

$method = $class->addMethod('one');
$method->addParameter('db', null);
$method->addBody('return parent::one($db);');
$method->addComment('@inheritdoc');
$method->addComment("@return {$modelFullClassName}");

$method = $class->addMethod('search');
$method->addComment('@var mixed $filter');
$method->addComment('@var array $options Options for ActiveDataProvider');
$method->addParameter('filter', null);
$method->addParameter('options', []);
$method->addComment("@return ActiveDataProvider");
$body = <<<PHP
\$this->filter(\$filter);
\$sort = new Sort();
    return new ActiveDataProvider(
    array_merge([
        'query' => \$this,
        'sort'  => \$sort
    ], \$options)
);
PHP;
$method->addBody($body);

$method = $class->addMethod('filter');
$method->addComment('@var mixed $model');
$method->addComment('@return $this');
$method->addParameter('model', null);
$body = <<<PHP
if (\$model){
//
}
return \$this;
PHP;

$method->addBody($body);

echo "<?php\n";
echo $namespace;
return;