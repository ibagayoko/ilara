<?php

namespace App\Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GraphQLSchemaGenerator implements Generator
{
    const INDENT = '    ';
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

        $stub =  $this->getStub('type');

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
        // Type definition
        $stub = str_replace('DummyType', $model->name(), $stub);
        
        $body = $this->buildFields($model);
        
        $stub = str_replace('// ...', trim($body), $stub);
        
        // Query definition
        $stub = str_replace('QuerySingle', Str::lower($model->name()), $stub);
        $stub = str_replace('QueryMultiple', Str::lower(Str::plural($model->name())), $stub);
        

        return $stub;
    }


    private function buildFields(Model $model)
    {
        $columns = $model->columns();

        if (empty($columns)) {
            return '';
        }


        $fields = '';
        /** @var Column $column */
        foreach ($columns as $column) {
            $field = '';
            
            
            $name = $column->name();
            $dataType = Str::studly($column->dataType());
            $dataType = $dataType=='Id'? 'ID' : $dataType;
            $dataType = $dataType=='Integer'? 'Int' : $dataType;
            $dataType = $dataType=='Text'? 'String' : $dataType;
            $dataType = $dataType=='Decimal'? 'Float' : $dataType;
            $belongTo ='';
            if(Str::endsWith($column->name(), '_id')){
                $name = Str::substr($column->name(), 0, -3);
                $class = $dataType=  Str::studly($column->attributes()[0] ?? $name);
                $belongTo = ' @belongsTo';
                
            }
            $field = self::INDENT .  $name . ': ' .$dataType;
            if (!in_array('nullable' , $column->modifiers())) {
                $field .=  '!';
            }
            $field .= $belongTo;

            $fields .=  $field .PHP_EOL;
        }
        
        if($model->usesTimestamps()){
            $fields .= self::INDENT . 'created_at: DateTime' . PHP_EOL;
            $fields .= self::INDENT . 'updated_at: DateTime' . PHP_EOL;
        }


        return $fields;
    }


    protected function getPath(Model $model)
    {
        $name = Str::snake($model->name());
        $folder = Str::snake(Str::beforeLast($name, '_'), '/');
        $folder = Str::finish($folder, '/');
        $fpath = base_path('graphql/'. $folder );
        $fs = new Filesystem;
        if (!$fs->exists($fpath)) $fs->makeDirectory($fpath, 0755, true);
        return  base_path('graphql/'. $folder .  $name .'.graphql');
    }

    private function getStub(string $stub)
    {
        static $stubs = [];

        if (empty($stubs[$stub])) {
            $stubs[$stub] = $this->files->get($this->stubs_path . '/schema/' . $stub . '.stub');
        }

        return $stubs[$stub];
    }

}