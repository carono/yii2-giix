<?php


namespace carono\giix;


use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ClassGenerator
{
    /**
     * @var \yii\gii\Generator
     */
    public $generator;
    public $namespace;
    public $extends;
    public $params;
    /**
     * @var ClassType
     */
    protected $class;
    /**
     * @var PhpNamespace
     */
    protected $owner;

    /**
     * ClassGenerator constructor.
     *
     * @param \yii\gii\Generator $generator
     */
    public function __construct($generator)
    {
        $this->generator = $generator;
    }

    /**
     * @return array
     */
    protected function uses()
    {
        return ['Yii'];
    }

    /**
     * @param $className
     * @return ClassType
     */
    protected function getClass($className)
    {
        if (!$this->class) {
            $namespace = new PhpNamespace($this->namespace);
            $this->owner = $namespace;
            return $this->class = $namespace->addClass($className);
        } else {
            return $this->class;
        }
    }

    protected function afterRender()
    {

    }

    /**
     * @param $className
     * @param $params
     * @return string
     */
    public function render($className, $params)
    {
        $class = $this->getClass($className);
        $this->class->addExtend($this->extends);
        $this->params = $params;
        $reflection = new \ReflectionClass($this);
        $except = ['render', '__construct'];
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!in_array($method->name, $except)) {
                $result = call_user_func([$this, $method->name], $class->addMethod($method->name));
                if ($result === false) {
                    $methods = $class->getMethods();
                    unset($methods[$method->name]);
                    $class->setMethods($methods);
                }
            }
        }
        foreach (array_filter($this->uses()) as $alias => $namespace) {
            $this->owner->addUse($namespace, is_numeric($alias) ? null : $alias);
        }
        foreach ($this->comments() as $comment) {
            $this->class->addComment($comment);
        }
        foreach (array_filter($this->properties()) as $property => $value) {
            $this->class->addProperty($property, $value);
        }
        foreach (array_filter($this->constants()) as $constant => $value) {
            $this->class->addConstant($constant, $value);
        }
        foreach (array_filter($this->traits()) as $trait => $resolutions) {
            $this->class->addTrait(is_numeric($trait) ? $resolutions : $trait, is_numeric($trait) ? [] : $resolutions);
        }
        $this->afterRender();
        return "<?php\n\n" . (string)$this->owner;
    }

    /**
     * @return array
     */
    protected function traits()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function comments()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function properties()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function constants()
    {
        return [];
    }
}