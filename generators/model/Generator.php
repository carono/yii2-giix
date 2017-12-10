<?php

namespace carono\giix\generators\model;

use carono\codegen\ClassGenerator;
use carono\giix\Event;
use schmunk42\giiant\helpers\SaveForm;
use yii\gii\CodeFile;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class Generator extends \schmunk42\giiant\generators\model\Generator
{
    public $jsonForms = false;
    public $relationNames = [];
    public $useTablePrefix = true;

    protected function findFiles($folder)
    {
        $result = [];
        $path = Yii::getAlias($folder);
        foreach (FileHelper::findFiles($path) as $file) {
            $result[substr($file, strlen($path) + 1)] = realpath($file);
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
        return preg_replace('#{{%([\w\d\-_]+)}}#', $prefix . "$1", $table);
    }


    public static function hideTablePrefix($table, $prefix)
    {
        if (preg_match("/^{$prefix}(.*?)$/", $table, $matches)) {
            return $table;
        } else {
            return preg_replace("/^{$prefix}(.*?)$/", "{{%$1}}", $table);
        }
    }

    protected function generateRelations()
    {
        $relations = parent::generateRelations();
        foreach ($this->relationNames as $table => $values) {
            $table = preg_replace('#{{%([\w\d\-_]+)}}#', $this->tablePrefix . "$1", $table);
            foreach (ArrayHelper::getValue($relations, $table, []) as $name => $relation) {
                foreach ($values as $value) {
                    $needModel = $value['model'] == $relation[1];
                    $needType = ($relation[2] && $value['type'] == 'many') || (!$relation[2] && $value['type'] == 'one');
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
                        $needFields = preg_match("/$pattern;/", $relation[0]);
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
            list($relations, $translations) = array_values($this->extractTranslations($tableName, $relations));

            $className = php_sapi_name() === 'cli'
                ? $this->generateClassName($tableName)
                : $this->modelClass;

            $queryClassName = ($this->generateQuery) ? $this->generateQueryClassName($className) : false;
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
            ];

            if (!empty($translations)) {
                $params['translation'] = $translations;
            }

            $params['blameable'] = $this->generateBlameable($tableSchema);
            $params['timestamp'] = $this->generateTimestamp($tableSchema);


            $templates = $this->getTemplateFiles();
            foreach ($templates as $file) {
                if ($code = $this->generateFile($file, $params)) {
                    $files[] = $code;
                }
            }
            /*
             * create gii/[name]GiiantModel.json with actual form data
             */
            if ($this->jsonForms) {
                $suffix = str_replace(' ', '', $this->getName());
                $formDataDir = Yii::getAlias('@' . str_replace('\\', '/', $this->ns));
                $formDataFile = StringHelper::dirname($formDataDir) . '/gii' . '/' . $tableName . $suffix . '.json';
                $formData = json_encode(SaveForm::getFormAttributesValues($this, $this->formAttributes()));
                $files[] = new CodeFile($formDataFile, $formData);
            }
        }

        return $files;
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

    /**
     * @param $filePath
     * @param $params
     * @return CodeFile
     * @throws \Exception
     */
    public function generateFile($filePath, $params)
    {
        $name = pathinfo($filePath, PATHINFO_FILENAME);
        $method = 'render' . Inflector::camelize($name);
        if ($className = self::getClassFromFile($filePath)) {
            $class = new $className();
            if (property_exists($class, 'generator')) {
                $class->generator = $this;
            }
        } else {
            $class = null;
        }
        if (method_exists($this, $method)) {
            $result = call_user_func_array([$this, $method], [$class, $params, $filePath]);
        } else {
            $result = $this->defaultRender($class, $params, $filePath);
        }
        $this->trigger('afterRender', new Event([
            'class' => $class,
            'params' => $params,
            'filePath' => $filePath,
            'render' => (boolean)$result
        ]));
        return $result;
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return null|CodeFile
     */
    protected function defaultRender($class, $params, $filePath)
    {
        if ($class instanceof ClassGenerator) {
            $content = $class->render($params);
            if ($output = $class->output) {
                return new CodeFile($output, $content);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return null|CodeFile
     */
    public function renderQueryExtended($class, $params, $filePath)
    {
        if ($class) {
            $queryClassName = $params['queryClassName'];
            if ($queryClassName) {
                $alias = '@' . str_replace('\\', '/', $this->queryNs);
                $output = Yii::getAlias($alias) . '/' . $queryClassName . '.php';
                if ($this->generateModelClass || !is_file($output)) {
                    return new CodeFile($output, $class->render($params));
                }
            }
            return null;
        } else {
            $queryClassName = $params['queryClassName'];
            $className = $params['className'];
            if ($queryClassName) {
                $alias = '@' . str_replace('\\', '/', $this->queryNs);
                $output = Yii::getAlias($alias) . '/' . $queryClassName . '.php';
                if ($this->generateModelClass || !is_file($output)) {
                    $params['className'] = $queryClassName;
                    $params['modelClassName'] = $className;
                    return new CodeFile($output, $this->render('query-extended.php', $params));
                }
            }
            return null;
        }
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return null|CodeFile
     */
    public function renderQuery($class, $params, $filePath)
    {
        if ($class) {
            $queryClassName = $params['queryClassName'];
            $className = $params['className'];
            if ($queryClassName) {
                if ($this->ns !== $this->queryNs) {
                    $params['modelFullClassName'] = '\\' . $this->ns . '\\' . $className;
                } else {
                    $params['modelFullClassName'] = $className;
                }
                $alias = '@' . str_replace('\\', '/', $this->queryNs);
                $output = Yii::getAlias($alias) . '/base/' . $queryClassName . '.php';
                $content = $class->render($params);
                return new CodeFile($output, $content);
            }
            return null;
        } else {
            $queryClassName = $params['queryClassName'];
            $className = $params['className'];
            if ($queryClassName) {
                $alias = '@' . str_replace('\\', '/', $this->queryNs);
                $queryClassFile = Yii::getAlias($alias) . '/base/' . $queryClassName . '.php';
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $className;
                return new CodeFile($queryClassFile, $this->render('query.php', $params));
            }
            return null;
        }
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return CodeFile
     */
    public function renderModel($class, $params, $filePath)
    {
        if ($class) {
            $className = $params['className'];
            $alias = '@' . str_replace('\\', '/', $this->ns);
            $outputPath = Yii::getAlias($alias) . '/base/' . $className . $this->baseClassSuffix . '.php';
            $content = $class->render($params);
            return new CodeFile($outputPath, $content);
        } else {
            $className = $params['className'];
            $alias = '@' . str_replace('\\', '/', $this->ns);
            $content = $this->render('model.php', $params);
            return new CodeFile(Yii::getAlias($alias) . '/base/' . $className . $this->baseClassSuffix . '.php', $content);
        }
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return null|CodeFile
     */
    public function renderModelExtended($class, $params, $filePath)
    {
        if ($class) {
            $className = $params['className'];
            $output = Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $className . '.php';
            if ($this->generateModelClass || !is_file($output)) {
                return new CodeFile($output, $class->render($params));
            } else {
                return null;
            }
        } else {
            $className = $params['className'];
            $output = Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $className . '.php';
            if ($this->generateModelClass || !is_file($output)) {
                return new CodeFile($output, $this->render('model-extended.php', $params));
            } else {
                return null;
            }
        }
    }

    public function requiredTemplates()
    {
        return [];
    }
}