<?php

namespace carono\giix;

use schmunk42\giiant\commands\BatchController;

class GiixController extends BatchController
{
    public $modelNamespace = 'app\models';
    public $overwrite = true;
    public $defaultAction = 'models';
    public $interactive = false;
    public $template = 'caronoModel';

    public function init()
    {
        try {
            \Yii::getAlias('@common');
            if ($this->modelNamespace == 'app\models') {
                $this->modelNamespace = 'common\models';
            }
            if ($this->modelQueryNamespace == 'app\models\query') {
                $this->modelQueryNamespace = 'common\models\query';
            }
        } catch (\Exception $e) {
        }
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