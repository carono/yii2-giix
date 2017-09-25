<?php

namespace carono\giix\templates\model;

use carono\codegen\ClassGenerator;
use carono\giix\generators\model\Generator;
use Nette\PhpGenerator\Method;

/**
 * Class Model
 *
 * @package carono\giix\templates\model
 * @property Generator $generator
 */
class Model extends ClassGenerator
{
    protected function classUses()
    {
        return ['Yii', 'yii\helpers\ArrayHelper','yii\db\ActiveRecord'];
    }

    /**
     * @return array
     */
    protected function phpProperties()
    {
        $relationClasses = [];
        foreach ($this->params['relations'] as $name => $relation) {
            if (preg_match('/.*hasOne.*\\\(.*)::className\(\).*\[.*\s=>\s\'(.*)\'\]/', $relation[0], $m)) {
                $relationClasses[$m[2]] = $this->generator->ns . "\\" . $relation[1];
            }
        }
        $property = $this->phpClass->addProperty('_relationClasses', $relationClasses);
        $property->setVisibility('protected');

        return [];
    }

    /**
     * @return array
     */
    protected function classConstants()
    {
        $constants = [];
        $enum = $this->params['enum'];
        foreach ($enum as $column_name => $column_data) {
            foreach ($column_data['values'] as $enum_value) {
                $constants[$enum_value['const_name']] = $enum_value['value'];
            }
        }
        return $constants;
    }

    /**
     * @return array
     */
    protected function classTraits()
    {
        return [$this->generator->baseTraits];
    }

    /**
     * @return array
     */
    protected function phpDocComments()
    {
        $comments = parent::phpDocComments();
        $tableName = $this->generator->generateTableName($this->params['tableName']);

        $comments[] = "This is the base-model class for table \"$tableName\".\n";

        foreach ($this->params['tableSchema']->columns as $column) {
            $comments[] = "@property {$column->phpType} \${$column->name}";
        }
        $relations = $this->params['relations'];
        $ns = $this->params['ns'];
        $comments[] = '';
        if (!empty($relations)) {
            foreach ($relations as $name => $relation) {
                $property = "\\" . $ns . '\\' . $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name);
                $comments[] = "@property $property";
            }
        }
        return $comments;
    }

    /**
     * @param Method $method
     * @return bool
     */
    public function getDb($method)
    {
        if (($db = $this->generator->db) !== 'db') {
            $method->addComment('@return \yii\db\Connection the database connection used by this AR class.');
            $method->addBody("return Yii::\$app->get('$db');");
            $method->setStatic();
        } else {
            return false;
        }
    }

    /**
     * @param Method $method
     */
    public function tableName($method)
    {
        $tableName = $this->params['tableName'];
        $method->addComment('@inheritdoc');
        $method->addBody("return '{{%$tableName}}';");
        $method->setStatic();
    }

    /**
     * @param Method $method
     */
    public function rules($method)
    {
        $method->addComment('@inheritdoc');
        $rules = $this->params['rules'];
        $method->addBody("return [\n            " . implode(",\n            ", $rules) . ",\n        ];");
    }

    /**
     * @param Method $method
     */
    public function findOne($method)
    {
        $ns = $this->params['ns'];
        $className = $this->params['className'];
        $method->addParameter('condition');
        $method->addParameter('raise', false);
        $method->addComment('@inheritdoc');
        $method->addComment("@return \\$ns\\$className|yii\db\ActiveRecord");
        $method->setStatic();
        $message = "Yii::t('errors', 'Model $ns\\$className not found')";
        $body = <<<PHP
\$model = parent::findOne(\$condition);
if (!\$model && \$raise){
    throw new \yii\web\HttpException(404, $message);
}else{
    return \$model;
}
PHP;
        $method->addBody($body);
    }

    /**
     * @param Method $method
     */
    public function attributeLabels($method)
    {
        $labels = $this->params['labels'];
        $method->addComment('@inheritdoc');
        $strings = '';
        foreach ($labels as $name => $label) {
            $strings[] = "\t'$name' => " . $this->generator->generateString($label);
        }
        $method->addBody("return [\n" . join(",\n", $strings) . "\n];");
    }

    /**
     * @param Method $method
     */
    public function find($method)
    {
        $queryClassName = $this->params['queryClassName'];
        $generator = $this->generator;
        $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
        $method->setStatic();
        $method->addComment('@inheritdoc');
        $method->addComment("@return $queryClassFullName the active query used by this AR class.");
        $method->addBody("return new $queryClassFullName(get_called_class());");
    }

    /**
     * @param Method $method
     * @return bool
     */
    public function relations($method)
    {
        $relations = $this->params['relations'];
        foreach ($relations as $name => $relation) {
            if (preg_match('/.*[hasOne|hasMany].*\\\(.*)::className\(\).*\[.*\s=>\s\'(.*)\'\]/', $relation[0], $m)) {
                $queryNs = '\\' . $this->generator->queryNs . '\\' . $m[1] . "Query|\yii\db\ActiveQuery";
            } else {
                $queryNs = '\yii\db\ActiveQuery';
            }
            $comment = "@return $queryNs";
            $method = $this->phpClass->addMethod("get{$name}");
            $method->addComment($comment);
            $method->addBody($relation[0]);
        }
        return false;
    }

    /**
     * @param Method $method
     * @return bool
     */
    public function enumLabels($method)
    {
        $enum = $this->params['enum'];
        foreach ($enum as $name => $value) {
            $method = $this->phpClass->addMethod($value['func_get_label_name']);
            $method->addParameter('value');
            $method->setStatic();
            $functionName = $value['func_opts_name'];
            $body = <<<PHP
return yii\helpers\ArrayHelper::getValue(self::$functionName(), \$value, \$value);            
PHP;
            $method->addBody($body);
            $method->addComment("get column $name ENUM value label");
            $method->addComment('@param string $value');
            $method->addComment('@return string');

            $method = $this->phpClass->addMethod($value['func_opts_name']);
            $method->setStatic();

            $strings = [];
            foreach ($value['values'] as $k => $labelValue) {
                $constName = $labelValue['const_name'];
                $label = $this->generator->generateString($labelValue['label']);
                $strings[] = "\tself::$constName => $label";
            }
            $method->addBody("return [\n" . join(",\n", $strings) . "\n];");
            $method->addComment("column $name ENUM value labels");
            $method->addComment('@return array');
        }
        return false;
    }

    /**
     * @param Method $method
     * @return null
     */
    public function getRelationClass($method)
    {
        $method->addParameter('attribute');
        $method->addComment('@param string $attribute');
        $method->addComment('@return string|null');
        $method->addBody('return ArrayHelper::getValue($this->_relationClasses, $attribute);');
    }
}