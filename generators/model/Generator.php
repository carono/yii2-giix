<?php

namespace carono\giix\generators\model;

use schmunk42\giiant\helpers\SaveForm;
use yii\gii\CodeFile;
use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class Generator extends \schmunk42\giiant\generators\model\Generator
{
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
                'tableName' => $tableName,
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


            foreach (FileHelper::findFiles($this->templatePath) as $file) {
                if ($code = $this->generateFile(basename($file), $params)) {
                    $files[] = $code;
                }
            }

            /*
             * create gii/[name]GiiantModel.json with actual form data
             */
            $suffix = str_replace(' ', '', $this->getName());
            $formDataDir = Yii::getAlias('@' . str_replace('\\', '/', $this->ns));
            $formDataFile = StringHelper::dirname($formDataDir) . '/gii' . '/' . $tableName . $suffix . '.json';
            $formData = json_encode(SaveForm::getFormAttributesValues($this, $this->formAttributes()));
            $files[] = new CodeFile($formDataFile, $formData);
        }

        return $files;
    }

    /**
     * @param $file
     * @param $params
     * @return CodeFile
     * @throws \Exception
     */
    public function generateFile($file, $params)
    {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $method = 'render' . Inflector::camelize($name);
        if (method_exists($this, $method)) {
            return call_user_func([$this, $method], $params);
        } else {
            throw new \Exception("Method " . self::className() . ":$method not found");
        }
    }

    public function renderQueryExtended($params)
    {
        $queryClassName = $params['queryClassName'];
        $className = $params['className'];
        if ($queryClassName) {
            $alias = '@' . str_replace('\\', '/', $this->queryNs);
            $queryClassFile = Yii::getAlias($alias) . '/' . $queryClassName . '.php';
            if ($this->generateModelClass || !is_file($queryClassFile)) {
                $params['className'] = $queryClassName;
                $params['modelClassName'] = $className;
                return new CodeFile($queryClassFile, $this->render('query-extended.php', $params));
            }
        }
        return null;
    }

    public function renderQuery($params)
    {
        $queryClassName = $params['queryClassName'];
        $className = $params['className'];
        if ($queryClassName) {
            $alias = '@' . str_replace('\\', '/', $this->queryNs);
            $queryClassFile = Yii::getAlias($alias) . '/../base/' . $queryClassName . '.php';
            $params['className'] = $queryClassName;
            $params['modelClassName'] = $className;
            return new CodeFile($queryClassFile, $this->render('query.php', $params));
        }
        return null;
    }

    public function renderModel($params)
    {
        $className = $params['className'];
        $alias = '@' . str_replace('\\', '/', $this->ns);
        $content = $this->render('model.php', $params);
        return new CodeFile(Yii::getAlias($alias) . '/base/' . $className . $this->baseClassSuffix . '.php', $content);
    }

    public function renderModelExtended($params)
    {
        $className = $params['className'];
        $modelClassFile = Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $className . '.php';
        if ($this->generateModelClass || !is_file($modelClassFile)) {
            return new CodeFile($modelClassFile, $this->render('model-extended.php', $params));
        } else {
            return null;
        }
    }
}