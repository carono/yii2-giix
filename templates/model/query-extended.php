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
 * @var string $queryClassName
 */

$modelFullClassName = $modelClassName;
if ($generator->ns !== $generator->queryNs) {
    $modelFullClassName = '\\' . $generator->ns . '\\' . $modelFullClassName;
}

$namespace = new PhpNamespace($generator->queryNs);
$class = $namespace->addClass($className);
$class->addComment("This is the ActiveQuery class for $modelFullClassName");
$class->addComment("@see $modelFullClassName");
$class->addExtend("{$generator->queryNs}\base\\$queryClassName");

echo "<?php\n";
echo $namespace;