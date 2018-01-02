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
        foreach(config('joptimize.optimize') as $method)
        {
            $optimizer = new Joptimize();
            foreach($method['parameters'] as $param)
            {
                $define = 'define' . ucfirst($param['type']);
                $optimizer->$define($param['name'], ...$param['args']);
            }

            $result = $optimizer->optimize($method['method']);
            foreach($result as $name => $value)
            {
                try {
                    $this->editVariable($name, $value);
                    $this->info("Optimized variable '$name' : $value");
                } 
                catch(\Exception $e) { 
                    $this->error($e->getMessage());
                }
            }
        }
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
