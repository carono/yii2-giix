<?php
namespace carono\giix;

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
				if (($gii = $app->getModule('gii')) && isset($gii->generators["giiant-model"])) {
					if (!isset($gii->generators["giiant-model"]["templates"])) {
						if (is_array($gii->generators["giiant-model"])) {
							$gii->generators["giiant-model"]["templates"] = [];
						} else {
							$gii->generators["giiant-model"] = [
								"class"     => 'schmunk42\giiant\generators\model\Generator',
								"templates" => []
							];
						}
					}
					$template = '@vendor/carono/yii2-components/templates/giiant-model';
					$gii->generators["giiant-model"]["templates"]["caronoModel"] = $template;
					$app->controllerMap['giix'] = 'carono\giix\commands\GiixController';
				}
			}
		}
	}
}