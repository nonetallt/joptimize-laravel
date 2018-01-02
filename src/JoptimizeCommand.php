<?php

namespace Nonetallt\Joptimize\Laravel;

use Illuminate\Console\Command;
use Nonetallt\Joptimize\Joptimize;

class JoptimizeCommand extends Command
{
    protected $signature = 'joptimize';
    protected $description = 'optimize values and save them to .env';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /* Print empty line to avoid cluttered output */
        $this->line('');
        foreach(config('joptimize.optimize') as $method)
        {
            /* Get initialization options from config */
            $initOptions = $method['init'] ?? [];
            $optimizer = new Joptimize($initOptions);

            foreach($method['parameters'] as $param)
            {
                /* Use config parameter type to call method, ex. defineLinear */
                $define = 'define' . ucfirst($param['type']);
                $parameter = $optimizer->$define($param['name'], ...$param['args']);
            }

            $this->defineProgressBarUpdates($optimizer);
            $result = $optimizer->optimize($method['method']);

            foreach($result as $name => $value)
            {
                try {
                    $value = $this->applyMutators($method, $name, $value);
                    $this->editVariable($name, $value);
                    $this->info(" Optimized variable '$name' : $value". PHP_EOL);
                } 
                catch(\Exception $e) { 
                    $this->error($e->getMessage());
                }
            }
        }
    }

    private function applyMutators(array $method, string $name, string $value)
    {
        /* Find if there is a mutate setting defined for this parameter */
        $mutate = collect($method['parameters'])->first(function($value) use ($name) {
            return $value['name'] === $name;
        })['mutate'] ?? null;

        /* Check if mutator can be called before applying the result of the
            * function */
        if(! is_null($mutate) && is_callable($mutate)) return $mutate($value);

        /* Otherwise return unmutated value */
        return $value;
    }

    private function defineProgressBarUpdates(Joptimize $optimizer)
    {
        /* Create a progress bar on the first iteration */
            $optimizer->onFirstIteration(function($info) {
                $progress = $this->output->createProgressBar($info->totalIterations);
                $progress->setFormatDefinition('custom','%message%'.PHP_EOL.'%current%/%max% %bar% %percent%%');
                $progress->setFormat('custom');
                $info->saveValue('progress', $progress);
            });

            $optimizer->onIterationStart(function($info) {
                $info->progress->setMessage("Testing parameter '{$info->name}' with value {$info->value}");
            });

            $optimizer->onIterationEnd(function($info) {
                $info->progress->advance();
            });

            $optimizer->onLastIteration(function($info)  {
                $info->progress->setMessage("Optimized '{$info->name}', best time was {$info->bestTime} with value {$info->bestValue}.");
                $info->progress->finish();
            });
    }

    private function editVariable(string $name, string $value)
    {
        $filepath = config('joptimize.env_path');
        if(is_null($filepath) ) throw new \Exception('Could not find config for joptimizer.env_path');
        if(!file_exists($filepath)) throw new \Exception("Could not find .env at expected path: '$filepath'");

        $content = $this->getContent($filepath);
        $lineIndex = $this->findLineWithSetting($content, $name);
        $this->editLine($content, $lineIndex, "$name=$value", $filepath);
    }

    private function editLine(string $content, int $lineIndex = null, string $setting, string $filepath)
    {
        $temp = base_path('joptimize.temp');
        $handle = fopen($temp, 'w');
        $output = [];

        foreach(explode(PHP_EOL, $content) as $index => $line)
        {
            if($index === $lineIndex) $output[] = $setting . PHP_EOL;
            else $output[] = $line . PHP_EOL;
        }

        /* Append missing setting */
        if( is_null($lineIndex) && $this->shouldCreate()) $output[] = $setting;

        fwrite($handle, implode('', $output));
        fclose($handle);
        rename($temp, $filepath);
    }

    private function shouldCreate()
    {
        return config('joptimize.create_missing_variables') === true;
    }

    private function findLineWithSetting(string $content, string $setting)
    {
        foreach(explode(PHP_EOL, $content) as $index => $line)
        {
            if($this->lineHasSetting($line, $setting)) return $index;
        }
        if(! $this->shouldCreate()) throw new \Exception("Variable '$setting' does not exist and will not be created. If you wish to change the default behaviour for missing variables, please set create_missing_variables to true in config/joptimize".PHP_EOL);
        return null;
    }

    private function getContent(string $filepath)
    {
        $content = '';
        $handle = fopen($filepath, 'r');
        if(! $handle) throw new \Exception("Could not read '$filepath'");

        while(($line = fgets($handle)) !== false)
        {
            $content .= $line;
        }
        fclose($handle);
        return $content;
    }

    private function lineHasSetting(string $line, string $setting)
    {
        return substr($line, 0, strlen($setting)) === $setting;
    }
}
