Установка
=========
`composer require carono/yii2-giix`

Введение
========
Компонент для массовой генерации базовых моделей на основе `schmunk42/yii2-giiant`  

Использование
=============
После подключения пакета, через bootstrap добавляется команда в консольное приложение  
`yii giix`


Генерируемые файлы  
==================
```
[app]
    [models] Неперезаписываемые модели
        [base] Перезаписываемые базовые модели
        [query] Неперезаписываемые модели запросов    
            [base] Перезаписываемые базовые модели запросов
```

Изменение шаблонов генератора
=============================
В конфиге настраиваем генератор
```php
 'controllerMap' => [
        'giix' => [
            'class' => 'carono\giix\GiixController',
            'templatePath' => '@app/templates/model',
            'generator' => [
                'class' => 'carono\giix\generators\model\Generator'
            ]
        ],
    ],
```    

Создаём новый класс, который будет создаваться на каждую таблицу
```php
<?php

namespace app\templates\model;

use carono\codegen\ClassGenerator;
use Nette\PhpGenerator\Method;

class Finder extends ClassGenerator
{
    protected function formExtends()
    {
        return 'yii\base\Model';
    }

    protected function formClassNamespace()
    {
        return 'app\models\finders';
    }

    protected function formClassName()
    {
        return $this->params['className'] . 'Finder';
    }

    protected function formOutputPath()
    {
        return \Yii::getAlias('@app/models/finders/' . $this->formClassName() . '.php');
    }

    /**
     * @param Method $method
     */
    public function myFunction($method)
    {
        $method->addParameter('param');
        $method->addBody('echo "Hello World";');
    }
}
```

На выходе получаем в папке `models/finders` файлы: 
```php
<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\finders;

class UserFinder extends \yii\base\Model
{
	public function myFunction($param)
	{
		echo "Hello World";
	}
}
```

Шаблоны
=======
|Класс|Описание
|-----|--------
|carono\giix\templates\model\Model|Базовый класс модели, перезаписывается
|carono\giix\templates\model\ModelExtended|Основной класс модели
|carono\giix\templates\model\Query|Базовый класс модели запроса, перезаписывается
|carono\giix\templates\model\QueryExtended|Основной класс запроса
