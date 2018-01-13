<?php

namespace carono\giix\generators\model;

use Yii;
use yii\helpers\Inflector;
use schmunk42\giiant\helpers\SaveForm;

/**
 * This generator will generate one or multiple ActiveRecord classes for the specified database table.
 *
 * @author Tobias Munk <schmunk@usrbin.de>
 *
 * @since 0.0.1
 */
class BaseGenerator extends \yii\gii\generators\model\Generator
{
    /**
     * @var null string for the table prefix, which is ignored in generated class name
     */
    public $tablePrefix = null;

    /**
     * @var string suffix to append to the base model, setting "Base" will result in a model named "PostBase"
     */
    public $baseClassSuffix = '';

    /**
     * @var array key-value pairs for mapping a table-name to class-name, eg. 'prefix_FOObar' => 'FooBar'
     */
    public $tableNameMap = [];
    public $singularEntities = false;

    /**
     * @var bool This indicates whether the generator should generate attribute hints by using the comments of the corresponding DB columns
     */
    public $generateHintsFromComments = true;

    /**
     * @var string form field for selecting and loading saved gii forms
     */
    public $savedForm;

    public $messageCategory = 'models';

    protected $classNames2;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Giiant Model';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'This generator generates an ActiveRecord class and base class for the specified database table.';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [
                    [
                        'generateHintsFromComments',
                        'singularEntities',
                    ],
                    'boolean'
                ],
                [
                    [
                        'savedForm'
                    ],
                    'string'
                ],
                [['tablePrefix'], 'safe'],
            ]
        );
    }

    /**
     * all form fields for saving in saved forms.
     *
     * @return array
     */
    public function formAttributes()
    {
        return [
            'tableName',
            'tablePrefix',
            'modelClass',
            'ns',
            'baseClass',
            'db',
            'generateRelations',
            //'generateRelationsFromCurrentSchema',
            'generateLabelsFromComments',
            'generateHintsFromComments',
            'generateModelClass',
            'generateQuery',
            'queryNs',
            'queryClass',
            'queryBaseClass',
            'enableI18N',
            'singularEntities',
            'messageCategory',
            'useTranslatableBehavior',
            'languageTableName',
            'languageCodeColumn',
            'useBlameableBehavior',
            'createdByColumn',
            'updatedByColumn',
            'useTimestampBehavior',
            'createdAtColumn',
            'updatedAtColumn',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'generateModelClass' => 'Generate Model Class',
                'generateHintsFromComments' => 'Generate Hints from DB Comments',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hints()
    {
        return array_merge(
            parent::hints(),
            [
                'generateModelClass' => 'This indicates whether the generator should generate the model class, this should usually be done only once. The model-base class is always generated.',
                'tablePrefix' => 'Custom table prefix, eg <code>app_</code>.<br/><b>Note!</b> overrides <code>yii\db\Connection</code> prefix!',
                'useTranslatableBehavior' => 'Use <code>2amigos/yii2-translateable-behavior</code> for tables with a relation to a translation table.',
                'languageTableName' => 'The name of the table containing the translations. <code>{{table}}</code> will be replaced with the value in "Table Name" field.',
                'languageCodeColumn' => 'The column name where the language code is stored.',
                'generateHintsFromComments' => 'This indicates whether the generator should generate attribute hints
                    by using the comments of the corresponding DB columns.',
                'useTimestampBehavior' => 'Use <code>TimestampBehavior</code> for tables with column(s) for created at and/or updated at timestamps.',
                'createdAtColumn' => 'The column name where the created at timestamp is stored.',
                'updatedAtColumn' => 'The column name where the updated at timestamp is stored.',
                'useBlameableBehavior' => 'Use <code>BlameableBehavior</code> for tables with column(s) for created by and/or updated by user IDs.',
                'createdByColumn' => "The column name where the record creator's user ID is stored.",
                'updatedByColumn' => "The column name where the record updater's user ID is stored.",
            ],
            SaveForm::hint()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function requiredTemplates()
    {
        return ['model.php', 'model-extended.php'];
    }


    /**
     * Generates a class name from the specified table name.
     *
     * @param string $tableName the table name (which may contain schema prefix)
     *
     * @return string the generated class name
     */
    public function generateClassName($tableName, $useSchemaName = null)
    {

        //Yii::trace("Generating class name for '{$tableName}'...", __METHOD__);
        if (isset($this->classNames2[$tableName])) {
            //Yii::trace("Using '{$this->classNames2[$tableName]}' for '{$tableName}' from classNames2.", __METHOD__);
            return $this->classNames2[$tableName];
        }

        if (isset($this->tableNameMap[$tableName])) {
            Yii::trace("Converted '{$tableName}' from tableNameMap.", __METHOD__);

            return $this->classNames2[$tableName] = $this->tableNameMap[$tableName];
        }

        if (($pos = strrpos($tableName, '.')) !== false) {
            $tableName = substr($tableName, $pos + 1);
        }

        $db = $this->getDbConnection();
        $patterns = [];
        $patterns[] = "/^{$this->tablePrefix}(.*?)$/";
        $patterns[] = "/^(.*?){$this->tablePrefix}$/";
        $patterns[] = "/^{$db->tablePrefix}(.*?)$/";
        $patterns[] = "/^(.*?){$db->tablePrefix}$/";

        if (strpos($this->tableName, '*') !== false) {
            $pattern = $this->tableName;
            if (($pos = strrpos($pattern, '.')) !== false) {
                $pattern = substr($pattern, $pos + 1);
            }
            $patterns[] = '/^' . str_replace('*', '(\w+)', $pattern) . '$/';
        }

        $className = $tableName;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $className = $matches[1];
                Yii::trace("Mapping '{$tableName}' to '{$className}' from pattern '{$pattern}'.", __METHOD__);
                break;
            }
        }

        $returnName = Inflector::id2camel($className, '_');
        if ($this->singularEntities) {
            $returnName = Inflector::singularize($returnName);
        }

        Yii::trace("Converted '{$tableName}' to '{$returnName}'.", __METHOD__);

        return $this->classNames2[$tableName] = $returnName;
    }

    /**
     * Generates the attribute hints for the specified table.
     *
     * @param \yii\db\TableSchema $table the table schema
     *
     * @return array the generated attribute hints (name => hint)
     *               or an empty array if $this->generateHintsFromComments is false
     */
    public function generateHints($table)
    {
        $hints = [];

        if ($this->generateHintsFromComments) {
            foreach ($table->columns as $column) {
                if (!empty($column->comment)) {
                    $hints[$column->name] = $column->comment;
                }
            }
        }

        return $hints;
    }

    /**
     * {@inheritdoc}
     */
    public function generateRelationName($relations, $table, $key, $multiple)
    {
        return parent::generateRelationName($relations, $table, $key, $multiple);
    }

    protected function generateRelations()
    {
        $relations = parent::generateRelations();

        // inject namespace
        $ns = "\\{$this->ns}\\";
        foreach ($relations as $model => $relInfo) {
            foreach ($relInfo as $relName => $relData) {

                $relations[$model][$relName][0] = preg_replace(
                    '/(has[A-Za-z0-9]+\()([a-zA-Z0-9]+::)/',
                    '$1__NS__$2',
                    $relations[$model][$relName][0]
                );
                $relations[$model][$relName][0] = str_replace('__NS__', $ns, $relations[$model][$relName][0]);
            }
        }

        return $relations;
    }

    /**
     * prepare ENUM field values.
     *
     * @param array $columns
     *
     * @return array
     */
    public function getEnum($columns)
    {
        $enum = [];
        foreach ($columns as $column) {
            if (!$this->isEnum($column)) {
                continue;
            }

            $column_camel_name = str_replace(' ', '', ucwords(implode(' ', explode('_', $column->name))));
            $enum[$column->name]['func_opts_name'] = 'opts' . $column_camel_name;
            $enum[$column->name]['func_get_label_name'] = 'get' . $column_camel_name . 'ValueLabel';
            $enum[$column->name]['values'] = [];

            $enum_values = explode(',', substr($column->dbType, 4, strlen($column->dbType) - 1));

            foreach ($enum_values as $value) {
                $value = trim($value, "()'");

                $const_name = strtoupper($column->name . '_' . $value);
                $const_name = preg_replace('/\s+/', '_', $const_name);
                $const_name = str_replace(['-', '_', ' '], '_', $const_name);
                $const_name = preg_replace('/[^A-Z0-9_]/', '', $const_name);

                $label = ucwords(
                    trim(strtolower(str_replace(['-', '_'], ' ', preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $value))))
                );
                $label = preg_replace('/\s+/', ' ', $label);

                $enum[$column->name]['values'][] = [
                    'value' => $value,
                    'const_name' => $const_name,
                    'label' => $label,
                ];
            }
        }

        return $enum;
    }

    /**
     * validate is ENUM.
     *
     * @param $column table column
     *
     * @return string
     */
    public function isEnum($column)
    {
        return substr(strtoupper($column->dbType), 0, 4) == 'ENUM';
    }

    /**
     * Generates validation rules for the specified table and add enum value validation.
     *
     * @param \yii\db\TableSchema $table the table schema
     *
     * @return array the generated validation rules
     */
    public function generateRules($table)
    {
        $columns = [];

        $rules = [];

        //for enum fields create rules "in range" for all enum values
        $enum = $this->getEnum($table->columns);
        foreach ($enum as $field_name => $field_details) {
            $ea = [];
            foreach ($field_details['values'] as $field_enum_values) {
                $ea[] = 'self::' . $field_enum_values['const_name'];
            }
            $rules[] = "['" . $field_name . "', 'in', 'range' => [\n                    " . implode(
                    ",\n                    ",
                    $ea
                ) . ",\n                ]\n            ]";
        }

        // inject namespace for targetClass
        $parentRules = parent::generateRules($table);
        $ns = "\\{$this->ns}\\";
        $match = "'targetClass' => ";
        $replace = $match . $ns;
        foreach ($parentRules as $k => $parentRule) {
            $parentRules[$k] = str_replace($match, $replace, $parentRule);
        }

        $rules = array_merge($parentRules, $rules);
        $table->columns = array_merge($table->columns, $columns);

        return $rules;
    }

    /**
     * @return \yii\db\Connection the DB connection from the DI container or as application component specified by [[db]]
     */
    protected function getDbConnection()
    {
        if (Yii::$container->has($this->db)) {
            return Yii::$container->get($this->db);
        } else {
            return Yii::$app->get($this->db);
        }
    }

    /**
     * Validates the [[db]] attribute.
     */
    public function validateDb()
    {
        if (Yii::$container->has($this->db)) {
            return true;
        } else {
            return parent::validateDb();
        }
    }

    public function getTableNames()
    {
        return parent::getTableNames();
    }
}
