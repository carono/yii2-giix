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


    public static function addTemplateToGiiGenerator($generator, $name, $template)
    {
        if ($gii = \Yii::$app->getModule('gii')) {
            $gii->generators[$generator]["templates"][$name] = $template;
        }
    }

    public static function addGeneratorToGii($name, $class)
    {
        $gii = \Yii::$app->getModule('gii');
//        if (isset($gii->generators[$name]) && is_array($gii->generators[$name])) {
//            $gii->generators[$name]["templates"] = [];
//        } else {
        $gii->generators[$name] = [
            "class"     => $class,
            "templates" => []
        ];
//        }
    }

//    public function actionCreateModule($id)
//    {
//        /**
//         * @var CodeFile $code
//         */
//        $generator = new Generator();
//        $generator->moduleID = $id;
//        $generator->moduleClass = 'app\modules\\' . strtolower($id) . '\\' . ucfirst($id) . "Module";
//        foreach ($generator->generate() as $code) {
//            $code->save();
//        };
//        $this->stdout("Module '$id' generated\n");
//        $this->stdout("Add to modules: '$id' => '{$generator->moduleClass}'\ns");
//    }
//
//    public function actionCreateController($id)
//    {
//        /**
//         * @var CodeFile $code
//         */
//        $id = str_replace('/', '\\', $id);
//        $arr = explode('\\', $id);
//        $generator = new \yii\gii\generators\controller\Generator();
//        if (count($arr) == 1) {
//            $c = ucfirst($arr[0]);
//            $generator->controllerClass = "app\\controllers\\{$c}Controller";
//            $generator->viewPath = "views\\" . strtolower($c);
//        } else {
//            $m = strtolower($arr[0]);
//            $c = ucfirst($arr[1]);
//            $generator->controllerClass = "app\\modules\\{$m}\\controllers\\{$c}Controller";
//            $generator->viewPath = "modules\\{$m}\\views\\" . strtolower($c);
//        }
//        foreach ($generator->generate() as $code) {
//            $code->save();
//        };
//        $this->stdout("Controller '$c' created");
//    }
//
//    public function actionCreateAction($id, $p = null)
//    {
//        $id = str_replace('/', '\\', $id);
//        $arr = explode('\\', $id);
//        $file = '';
//        $c = '';
//        $a = '';
//        $fileView = '';
//        if (count($arr) == 3) {
//            $m = $arr[0];
//            $c = ucfirst($arr[1]);
//            $c1 = strtolower($arr[1]);
//            $a = ucfirst($arr[2]);
//            $a1 = strtolower($a);
//            $file = \Yii::getAlias("@app/modules/$m/controllers/{$c}Controller.php");
//            $fileView = \Yii::getAlias("@app/modules/$m/views/{$c1}/$a1.php");
//        } elseif (count($arr) == 2) {
//            $c = ucfirst($arr[0]);
//            $c1 = strtolower($arr[0]);
//            $a = ucfirst($arr[1]);
//            $a1 = strtolower($a);
//            $file = \Yii::getAlias("@app/controllers/{$c}Controller.php");
//            $fileView = \Yii::getAlias("@app/views/{$c1}/$a1.php");
//        } else {
//        }
//        if (file_exists($file)) {
//            $content = trim(file_get_contents($file));
//            if (strpos($content, "action" . $a . "(") === false) {
//                $code = $this->getActionTemplate($a, explode(",", $p));
//                $new = preg_replace("/}$/", $code . "\n}", $content);
//                file_put_contents($file, $new);
//            }
//            if (!file_exists($fileView)) {
//                $codeFile = new CodeFile($fileView, "<?php \n //$c/$a");
//                $codeFile->save();
//            }
//        }
//    }

    protected function getActionTemplate($id, $params = [])
    {
        $id = ucfirst($id);
        $view = strtolower($id);
        $p = [];
        foreach (array_filter($params) as $value) {
            $p[] = "$" . trim($value);
        }
        $p = join(', ', $p);
        return <<<EOT
    public function action$id($p)
    {
        return \$this->render('$view');
    }
EOT;
    }
}