<?php

namespace carono\giix\generators\model;

use carono\codegen\ClassGenerator;
use carono\giix\Event;
use yii\gii\CodeFile;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class Generator extends BaseGenerator
{
    public $jsonForms = false;
    public $relationNames = [];
    public $useTablePrefix = true;
    protected $process = [];

    protected function findFiles($folder)
    {
        $result = [];
        $path = Yii::getAlias($folder);
        foreach (FileHelper::findFiles($path) as $file) {
            $result[pathinfo($file, PATHINFO_FILENAME)] = realpath($file);
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function getTemplateFiles()
    {
        $defaultFiles = $this->findFiles('@vendor/carono/yii2-giix/templates/model');
        $currentFiles = $this->findFiles($this->templatePath);
        $templates = $defaultFiles;

        foreach ($defaultFiles as $file => $path) {
            if (isset($currentFiles[$file]) && file_exists($currentFiles[$file])) {
                $templates[$file] = $currentFiles[$file];
            }
        }
        foreach ($currentFiles as $file => $path) {
            if (!isset($templates[$file])) {
                $templates[$file] = $path;
            }
        }

        foreach ($templates as $file => &$value) {
            $value = self::getClassFromFile($value);
        }
        return $templates;
    }

    public static function expandTablePrefix($table, $tablePrefix)
    {
        return self::setTablePrefix($table, $tablePrefix);
    }

    /**
     * @param $table
     * @param $prefix
     * @return mixed
     * @internal param $prefix
     */
    public static function setTablePrefix($table, $prefix)
    {
        return preg_replace('#{{%([\w\-_]+)}}#', $prefix . '$1', $table);
    }

    public static function hideTablePrefix($table, $prefix)
    {
        if (preg_match("/^{$prefix}(.*?)$/", $table, $matches)) {
            return $table;
        }

        return preg_replace("/^{$prefix}(.*?)$/", '{{%$1}}', $table);
    }

    protected function generateRelations()
    {
        $relations = parent::generateRelations();
        foreach ($this->relationNames as $table => $values) {
            $table = preg_replace('#{{%([\w\-_]+)}}#', $this->tablePrefix . "$1", $table);
            foreach (ArrayHelper::getValue($relations, $table, []) as $name => $relation) {
                foreach ($values as $value) {
                    $needModel = $value['model'] === $relation[1];
                    $needType = ($relation[2] && $value['type'] === 'many') || (!$relation[2] && $value['type'] === 'one');
                    $pattern = "\['{$value['refField']}' => '{$value['field']}'\]\)";
                    if (isset($value['via'])) {
                        if ($this->useTablePrefix) {
                            $viaTable = self::hideTablePrefix($value['via']['refTable'], $this->tablePrefix);
                        } else {
                            $viaTable = self::expandTablePrefix($value['via']['refTable'], $this->tablePrefix);
                        }
                        $pattern2 = "\['{$value['via']['refField']}' => '{$value['via']['field']}'\]";
                        $pattern3 = "{$pattern}->viaTable\('$viaTable', $pattern2\);";
                        $needFields = preg_match("/$pattern3/", $relation[0]);
                    } else {
                        $needFields = preg_match("/$pattern;/", $relation[0]) && !preg_match('/viaTable/i', $relation[0]);
                    }
                    if ($needModel && $needType && $needFields) {
                        unset($relations[$table][$name]);
                        $relations[$table][ucfirst($value['name'])] = $relation;
                    }
                }
            }
        }
        return $relations;
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $files = [];
        $relations = $this->generateRelations();
        $db = $this->getDbConnection();

        foreach ($this->getTableNames() as $tableName) {

            $className = php_sapi_name() === 'cli' ? $this->generateClassName($tableName) : $this->modelClass;

            $queryClassName = $this->generateQuery ? $this->generateQueryClassName($className) : false;
            $tableSchema = $db->getTableSchema($tableName);

            $params = [
                'tableName' => substr($tableName, strlen($db->tablePrefix)),
                'className' => $className,
                'queryClassName' => $queryClassName,
                'tableSchema' => $tableSchema,
                'baseClass' => $this->baseClass,
                'queryNs' => $this->queryNs,
                'queryBaseClass' => $this->queryBaseClass,
                'labels' => $this->generateLabels($tableSchema),
                'hints' => $this->generateHints($tableSchema),
                'rules' => $this->generateRules($tableSchema),
                'relations' => isset($relations[$tableName]) ? $relations[$tableName] : [],
                'ns' => $this->ns,
                'enum' => $this->getEnum($tableSchema->columns),
                'baseClassSuffix' => $this->baseClassSuffix
            ];

            $this->process = $this->getTemplateFiles();
            foreach ($this->process as $file) {
                if ($codes = $this->generateClass($file, $params)) {
                    $files = array_merge($files, $codes);
                }
            }
        }

        return $files;
    }

    protected function getProcessClassByBasename($basename)
    {
        foreach ($this->process as $name => $class) {
            if ($name === $basename || $basename === $class) {
                return $class;
            }
        }
        return null;
    }

    /**
     * @param ClassGenerator $className
     * @param $params
     * @return CodeFile[]
     */
    public function generateClass($className, $params)
    {
        $class = new $className();
        $class->generator = $this;
        $result = [];
//        if ($class instanceof \carono\giix\ClassGenerator) {
//            foreach ($class->depends as $basename) {
//                if ($dependClass = $this->getProcessClassByBasename($basename)) {
//                    $result = array_merge($this->generateClass($dependClass, $params), $result);
//                }
//            }
//        }
        $result[] = $currentResult = $this->defaultRender($class, $params);

        unset($this->process[StringHelper::basename($className)]);


        $this->trigger('afterRender', new Event([
            'class' => $class,
            'params' => $params,
            'render' => (boolean)$currentResult
        ]));
        return array_filter($result);
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return null|CodeFile
     */
    protected function defaultRender($class, $params)
    {
        if ($class instanceof ClassGenerator) {
            $content = $class->render($params);
            if ($class instanceof \carono\giix\ClassGenerator && $class->skipIfExist && file_exists($class->output)) {
                return null;
            }
            if ($output = $class->output) {
                return new CodeFile($output, $content);
            }
        }

        return null;
    }

    public function requiredTemplates()
    {
        return [];
    }

    /**
     * @param $path_to_file
     * @link http://jarretbyrne.com/2015/06/197/
     * @return mixed|string
     */
    public static function getClassFromFile($path_to_file)
    {
        //Grab the contents of the file
        $contents = file_get_contents($path_to_file);

        //Start with a blank namespace and class
        $namespace = $class = "";

        //Set helper values to know that we have found the namespace/class token and need to collect the string values after them
        $getting_namespace = $getting_class = false;

        //Go through each token and evaluate it as necessary
        foreach (token_get_all($contents) as $token) {

            //If this token is the namespace declaring, then flag that the next tokens will be the namespace name
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $getting_namespace = true;
            }

            //If this token is the class declaring, then flag that the next tokens will be the class name
            if (is_array($token) && $token[0] == T_CLASS) {
                $getting_class = true;
            }

            //While we're grabbing the namespace name...
            if ($getting_namespace === true) {

                //If the token is a string or the namespace separator...
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {

                    //Append the token's value to the name of the namespace
                    $namespace .= $token[1];

                } else if ($token === ';') {

                    //If the token is the semicolon, then we're done with the namespace declaration
                    $getting_namespace = false;

                }
            }

            //While we're grabbing the class name...
            if ($getting_class === true) {

                //If the token is a string, it's the name of the class
                if (is_array($token) && $token[0] == T_STRING) {

                    //Store the token's value as the class name
                    $class = $token[1];

                    //Got what we need, stope here
                    break;
                }
            }
        }

        //Build the fully-qualified class name and return it
        return $namespace ? $namespace . '\\' . $class : $class;

    }
}