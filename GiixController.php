<?php

namespace carono\giix;

use schmunk42\giiant\commands\BatchController;
use schmunk42\giiant\generators\model\Generator;

class GiixController extends BatchController
{
    public $modelNamespace = 'app\models';
    public $overwrite = true;
    public $defaultAction = 'models';
    public $interactive = false;
    public $template = 'caronoModel';
    public $templatePath;

    public function init()
    {
        if (in_array('common',\Yii::$aliases)){
            \Yii::getAlias('@common');
            if ($this->modelNamespace == 'app\models') {
                $this->modelNamespace = 'common\models';
            }
            if ($this->modelQueryNamespace == 'app\models\query') {
                $this->modelQueryNamespace = 'common\models\query';
            }
        }
        if ($this->templatePath) {
        }
    }

    protected function getYiiConfiguration()
    {
        $config = parent::getYiiConfiguration();

        $config['modules']['gii'] = [
            'class'      => 'yii\gii\Module',
            'generators' => [
                'giiant-model' => [
                    'class'     => Generator::className(),
                    'templates' => [
                        'caronoModel' => '@vendor/carono/yii2-giix/templates/model'
                    ]
                ]
            ]
        ];
        return $config;
    }

    public static function addTemplateToGiiGenerator($generator, $name, $template)
    {
        if ($gii = \Yii::$app->getModule('gii')) {
            $gii->generators[$generator]["templates"][$name] = $template;
        }
    }

    public static function addGeneratorToGii($name, $class)
    {
        \Yii::$app->getModule('gii')->generators[$name] = [
            "class"     => $class,
            "templates" => []
        ];
    }
}