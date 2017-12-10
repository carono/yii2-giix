<?php

namespace carono\giix;

use schmunk42\giiant\commands\BatchController;

class GiixController extends BatchController
{
    public $exceptTables = [];
    public $modelNamespace = 'app\models';
    public $overwrite = true;
    public $defaultAction = 'models';
    public $interactive = false;
    public $template = 'caronoModel';
    public $templatePath;
    public $generator = 'carono\giix\generators\model\Generator';
    public $relationNames = [];

    public function init()
    {
        if (!$this->tablePrefix) {
            $this->tablePrefix = \Yii::$app->db->tablePrefix;
        }
        foreach ($this->tables as &$table) {
            $table = preg_replace('#{{%([\w\d\-_]+)}}#', $this->tablePrefix . "$1", $table);
        }
        foreach ($this->exceptTables as &$table) {
            $table = preg_replace('#{{%([\w\d\-_]+)}}#', $this->tablePrefix . "$1", $table);
        }
        if (key_exists('@common', \Yii::$aliases)) {
            if ($this->modelNamespace == 'app\models') {
                $this->modelNamespace = 'common\models';
            }
            if ($this->modelQueryNamespace == 'app\models\query') {
                $this->modelQueryNamespace = 'common\models\query';
            }
        }
    }

    protected function getYiiConfiguration()
    {
        $config = parent::getYiiConfiguration();
        $name = 'giiant-model';
        $template = $this->templatePath ? $this->templatePath : '@vendor/carono/yii2-giix/templates/model';
        self::addTemplateToGiiGenerator($config, $this->generator, $name, $template);
        $config['modules']['gii']['generators'][$name]['relationNames'] = $this->relationNames;
        return $config;
    }

    public static function addTemplateToGiiGenerator(&$config, $generator, $name, $template)
    {
        self::prepareGii($config);
        $config['modules']['gii']['generators'][$name] = [
            'class' => $generator,
            'templates' => [
                'caronoModel' => $template
            ]
        ];
    }

    protected static function prepareGii(&$config)
    {
        if (!is_array($config['modules']['gii'])) {
            $config['modules']['gii'] = [
                'class' => 'yii\gii\Module',
                'generators' => []
            ];
        } elseif (isset($config['modules']['gii']['generators'])) {
            $config['modules']['gii']['generators'] = [];
        }
    }
}