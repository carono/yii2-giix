<?php

namespace carono\giix\generators\model;

use carono\codegen\ClassGenerator;
use schmunk42\giiant\helpers\SaveForm;
use yii\gii\CodeFile;
use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class Generator extends \schmunk42\giiant\generators\model\Generator
{
    public $jsonForms = false;

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
            return call_user_func_array([$this, $method], [$class, $params, $filePath]);
        } else {
            return $this->defaultRender($class, $params, $filePath);
        }
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
        $queryClassName = $params['queryClassName'];
        $className = $params['className'];
        if ($queryClassName) {
            $alias = '@' . str_replace('\\', '/', $this->queryNs);
            $queryClassFile = Yii::getAlias($alias) . '/' . $queryClassName . '.php';
            if ($this->generateModelClass || !is_file($queryClassFile)) {
                $class->namespace = $this->queryNs;
                $class->extends = "{$this->queryNs}\base\\$queryClassName";
                $class->className = $queryClassName;
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $className;
                return new CodeFile($queryClassFile, $class->render($params));
            }
        }
        return null;
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return null|CodeFile
     */
    public function renderQuery($class, $params, $filePath)
    {
        $queryClassName = $params['queryClassName'];
        $className = $params['className'];
        if ($queryClassName) {
            if ($this->ns !== $this->queryNs) {
                $params['modelFullClassName'] = '\\' . $this->ns . '\\' . $className;
            } else {
                $params['modelFullClassName'] = $className;
            }
            $class->namespace = "{$this->queryNs}\base";
            $class->extends = $this->queryBaseClass;
            $class->className = $params['queryClassName'];
            $alias = '@' . str_replace('\\', '/', $this->queryNs);
            $output = Yii::getAlias($alias) . '/base/' . $queryClassName . '.php';
            $params['className'] = $queryClassName;
            $params['modelClassName'] = $className;
            $content = $class->render($params);
            return new CodeFile($output, $content);
        }
        return null;
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return CodeFile
     */
    public function renderModel($class, $params, $filePath)
    {
        $className = $params['className'];
        $alias = '@' . str_replace('\\', '/', $this->ns);
        $class->namespace = $params['ns'] . "\base";
        $class->extends = '\\' . ltrim($this->baseClass, '\\');
        $class->className = $params['className'];
        $outputPath = Yii::getAlias($alias) . '/base/' . $className . $this->baseClassSuffix . '.php';
        $content = $class->render($params);
        return new CodeFile($outputPath, $content);
    }

    /**
     * @param ClassGenerator|null $class
     * @param $params
     * @param $filePath
     * @return null|CodeFile
     */
    public function renderModelExtended($class, $params, $filePath)
    {
        $className = $params['className'];
        $modelClassFile = Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $className . '.php';
        if ($this->generateModelClass || !is_file($modelClassFile)) {
            $class->extends = $this->ns . '\base\\' . $className;
            $class->namespace = $this->ns;
            $class->className = $className;
            $content = $class->render($params);
            return new CodeFile($modelClassFile, $content);
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
    public function renderSearch($class, $params, $filePath)
    {
        $className = $params['className'];
        $modelClassFile = Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $className . '.php';
        if (!is_file($modelClassFile)) {
            return new CodeFile($modelClassFile, $this->render('search.php', $params));
        } else {
            return null;
        }
    }

    public function requiredTemplates()
    {
        return [];
    }
}