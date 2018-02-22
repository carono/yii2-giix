<?php

namespace carono\giix;

use carono\giix\generators\model\Generator as ModelGenerator;
use yii\console\Controller;
use yii\helpers\Inflector;

/**
 * @author Tobias Munk <schmunk@usrbin.de>
 *
 * @property array $yiiConfiguration
 */
class BatchController extends Controller
{
    public $exceptTables = ['{{%migration}}'];

    /**
     * @var string the generator template name
     */
    public $template = 'default';

    /**
     * @var bool whether to generate and overwrite all files
     */
    public $overwrite = false;

    /**
     * @var array table names for generating models and CRUDs
     */
    public $tables = [];

    /**
     * @var string eg. `app_`
     */
    public $tablePrefix = '';

    /**
     * @var array mapping for table name to model class names
     */
    public $tableNameMap = [];

    /**
     * @var string namespace path for model classes
     */
    public $modelNamespace = 'common\\models';

    /**
     * @var string suffix to append to the base model, setting "Base" will result in a model named "PostBase"
     */
    public $modelBaseClassSuffix = '';

    /**
     * @var string database application component
     */
    public $modelDb = 'db';

    /**
     * @var string base class for the generated models
     */
    public $modelBaseClass = 'yii\db\ActiveRecord';

    /**
     * @var bool whether the strings will be generated using `Yii::t()` or normal strings
     */
    public $enableI18N = true;

    /**
     * @var bool whether the entity names will be singular or the same as the table name
     */
    public $singularEntities = true;

    /**
     * @var string the message category for models used by `Yii::t()` when `$enableI18N` is `true`.
     *             Defaults to `app`
     */
    public $modelMessageCategory = 'models';

    /**
     * @var string the message category for CRUDs used by `Yii::t()` when `$enableI18N` is `true`.
     *             Defaults to `app`
     */

    /**
     * @var bool indicates whether to generate ActiveQuery for the ActiveRecord class
     */
    public $modelGenerateQuery = true;

    /**
     * @var string the namespace of the ActiveQuery class to be generated
     */
    public $modelQueryNamespace = 'app\models\query';

    /**
     * @var string the base class of the new ActiveQuery class
     */
    public $modelQueryBaseClass = 'yii\db\ActiveQuery';

    /**
     * @var bool This indicates whether the generator should generate attribute labels by using the comments of the corresponding DB columns
     */
    public $modelGenerateLabelsFromComments = false;

    /**
     * @var bool This indicates whether the generator should generate attribute hints by using the comments of the corresponding DB columns
     */
    public $modelGenerateHintsFromComments = true;
    /**
     * @var array application configuration for creating temporary applications
     */
    protected $appConfig;

    /**
     * @var \carono\giix\generators\model\Generator
     */
    protected $modelGenerator;

    /**
     * {@inheritdoc}
     */
    public function options($id)
    {
        return array_merge(
            parent::options($id),
            [
                'template',
                'overwrite',
                'extendedModels',
                'enableI18N',
                'messageCategory',
                'singularEntities',
                'tables',
                'tablePrefix',
                'modelDb',
                'modelNamespace',
                'modelBaseClass',
                'modelBaseClassSuffix',
                'modelGenerateQuery',
                'modelQueryNamespace',
                'modelQueryBaseClass',
                'modelGenerateLabelsFromComments',
                'modelGenerateHintsFromComments'
            ]
        );
    }

    /**
     * Loads application configuration and checks tables parameter.
     *
     * @param \yii\base\Action $action
     *
     * @return bool
     */
    public function beforeAction($action)
    {
        $this->appConfig = $this->getYiiConfiguration();
        $this->appConfig['id'] = 'temp';
        $this->modelGenerator = new ModelGenerator(['db' => $this->modelDb]);

        if (!$this->tables) {
            $this->modelGenerator->tableName = '*';
            $this->tables = $this->modelGenerator->getTableNames();
            $tableList = implode("\n\t- ", $this->tables);
            $msg = "Are you sure that you want to run action \"{$action->id}\" for the following tables?\n\t- {$tableList}\n\n";
            if (!$this->confirm($msg)) {
                return false;
            }
        }
        $this->tables = array_diff($this->tables, $this->exceptTables);

        return parent::beforeAction($action);
    }

    /**
     * Run batch process to generate models and CRUDs for all given tables.
     *
     * @param string $message the message to be echoed
     */
    public function actionIndex()
    {
        echo "Running model batch...\n";
        $this->actionModels();
    }

    /**
     * Run batch process to generate models all given tables.
     *
     * @throws \yii\console\Exception
     */
    public function actionModels()
    {
        foreach ($this->tables as $table) {
            $params = [
                'interactive' => $this->interactive,
                'overwrite' => $this->overwrite,
                'template' => $this->template,
                'ns' => $this->modelNamespace,
                'db' => $this->modelDb,
                'tableName' => $table,
                'tablePrefix' => $this->tablePrefix,
                'enableI18N' => $this->enableI18N,
                'singularEntities' => $this->singularEntities,
                'messageCategory' => $this->modelMessageCategory,
                'baseClassSuffix' => $this->modelBaseClassSuffix,
                'modelClass' => isset($this->tableNameMap[$table]) ? $this->tableNameMap[$table] : Inflector::camelize($table),
                'baseClass' => $this->modelBaseClass,
                'tableNameMap' => $this->tableNameMap,
                'generateQuery' => $this->modelGenerateQuery,
                'queryNs' => $this->modelQueryNamespace,
                'queryBaseClass' => $this->modelQueryBaseClass,
                'generateLabelsFromComments' => $this->modelGenerateLabelsFromComments,
                'generateHintsFromComments' => $this->modelGenerateHintsFromComments,
            ];
            $route = 'gii/carono-model';

            $app = \Yii::$app;
            $temp = new \yii\console\Application($this->appConfig);
            $temp->runAction(ltrim($route, '/'), $params);
            unset($temp);
            \Yii::$app = $app;
            \Yii::$app->log->logger->flush(true);
        }
    }

    /**
     * Returns Yii's initial configuration array.
     *
     * @todo should be removed, if this issue is closed -> https://github.com/yiisoft/yii2/pull/5687
     *
     * @return array
     */
    protected function getYiiConfiguration()
    {
        if (isset($GLOBALS['config'])) {
            $config = $GLOBALS['config'];
        } else {
            $config = \yii\helpers\ArrayHelper::merge(
                require(\Yii::getAlias('@app') . '/../common/config/main.php'),
                (is_file(\Yii::getAlias('@app') . '/../common/config/main-local.php')) ?
                    require(\Yii::getAlias('@app') . '/../common/config/main-local.php')
                    : [],
                require(\Yii::getAlias('@app') . '/../console/config/main.php'),
                (is_file(\Yii::getAlias('@app') . '/../console/config/main-local.php')) ?
                    require(\Yii::getAlias('@app') . '/../console/config/main-local.php')
                    : []
            );
        }

        return $config;
    }
}
