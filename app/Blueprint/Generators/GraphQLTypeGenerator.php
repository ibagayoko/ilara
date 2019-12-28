<?php

namespace App\Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Illuminate\Support\Str;

class GraphQLTypeGenerator implements Generator
{
    const INDENT = '            ';
    protected $stubs_path ;

    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
        $this->stubs_path = dirname(__DIR__) . '../../Console/stubs/';
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub =  $this->getStub('class');

        /** @var \Blueprint\Models\Model $model */
        foreach ($tree['models'] as $model) {
            $path = $this->getPath($model);
            $this->files->put(
                $path,
                $this->populateStub($stub, $model)
            );

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('DummyNamespace', 'App\GraphQL\Types', $stub);
        $stub = str_replace('DummyClass', $model->name().'Type', $stub);

        $body = $this->buildAttributes($model);
        $body .= PHP_EOL . PHP_EOL;

        $body .= $this->buildFieldsMethod($model);

        $stub = str_replace('// ...', trim($body), $stub);

        return $stub;
    }

    private function buildAttributes(Model $model)
    {
        $attrs = '';
        $attrs = str_replace('DummyGraphqlName', $model->name(), $this->getStub('attributes'));
        $attrs = str_replace('DummyModel', '\App\\'.$model->name().'::class', $attrs);


        return $attrs;
    }


    private function buildFieldsMethod(Model $model)
    {
        $columns = $model->columns();

        if (empty($columns)) {
            return '';
        }

        $template = $this->getStub('fields');
        $fieldTemplate = $this->getStub('field');
        $fields ='[' . PHP_EOL;


        /** @var Column $column */
        foreach ($columns as $column) {
            $name = $column->name();
            $field = str_replace('DummyFieldName', Str::camel($name), $fieldTemplate);
            $field = str_replace('DummyDescription','The '. $name . ' of ' .$model->name(), $field);
            
            $dataType = $column->dataType();
            $dataType = $dataType == 'integer' ? 'int' : $dataType;
            $dataType = in_array($dataType, ['date', 'text']) ? 'string' : $dataType;
            $dataType = in_array($dataType, ['decimal']) ? 'float' : $dataType;
            $definition = 'Type::'. $dataType.'()';

            foreach ($column->modifiers() as $modifier) {
                $modifier = is_array($modifier) ?  key($modifier) : $modifier;
                if (!in_array($modifier, ['nullable', 'unique'])) {
                    $definition = "Type::" . $modifier . '(' . $definition . ')';
                }
            }
            if (!in_array('nullable' , $column->modifiers())) {
                $definition = "Type::nonNull(" . $definition . ')';
            }

            $field = str_replace('DummyType', $definition, $field);

            $fields .= PHP_EOL . $field;
        }
        $fields .= PHP_EOL . ']';
        $fields = str_replace('[]', $fields, $template);
        return $fields;
    }



    protected function getPath(Model $model)
    {
        return 'app/GraphQL/Types/' . $model->name() . 'Type.php';
    }

    private function getStub(string $stub)
    {
        static $stubs = [];

        if (empty($stubs[$stub])) {
            $stubs[$stub] = $this->files->get($this->stubs_path . '/type/' . $stub . '.stub');
        }

        return $stubs[$stub];
    }

}