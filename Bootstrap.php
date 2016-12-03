<?php
namespace carono\giix;

use schmunk42\giiant\generators\model\Generator;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\gii\Module;

/**
 * Class Bootstrap
 *
 * @package carono\giix
 */
class Bootstrap implements BootstrapInterface
{

    /**
     * Bootstrap method to be called during application bootstrap stage.
     *
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        /**
         * @var Module $gii
         */
        if ($app instanceof \yii\console\Application) {
            if (!isset($app->controllerMap['giix'])) {
                $app->controllerMap['giix'] = 'carono\giix\GiixController';
            }
        }
    }

}