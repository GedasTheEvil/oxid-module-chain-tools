<?php

$env = isset($argv[1]) ? $argv[1] : 'vagrant';
$useShopId = isset($argv[2]) ? $argv[2] : 1;
$shopId = isset($argv[3]) ? $argv[3] : 1;

define('CUSTOM_CONFIG_PATH', __DIR__ . '/dev/config/');
define('SHOP_ENV_LOCAL', 'local');


class PhpMetaGenerator
{
    const MAP_TO = [
        '\oxNew',
        '\twtNew',
        '\oxRegistry::get',
    ];

    /**
     * @var string
     */
    private $env;

    /**
     * @var int
     */
    private $useShopId;

    /**
     * @var array
     */
    private $aModules = [];

    /**
     * @var int
     */
    private $shopId;

    public function __construct($env, $useShopId = 1, $shopId = 1)
    {
        $this->env = $env;
        $this->useShopId = $useShopId;
        $this->shopId = $shopId;
    }

    public function generate()
    {
        @include CUSTOM_CONFIG_PATH . $this->env . '/modules/0.php';
        @include CUSTOM_CONFIG_PATH . $this->env . "/modules/{$this->shopId}.php";
        $dir = __DIR__ . '/.phpstorm.meta.php';

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $outputFile = "{$dir}/oxid.{$this->env}.{$this->shopId}.meta.php";
        $this->dump($outputFile);
    }

    private function dump($outputFile)
    {
        $source = $this->useShopId ? $this->aModules[$this->shopId] : $this->aModules;
        $file = fopen($outputFile, 'wb');
        fwrite($file, "<?php\n\nnamespace PHPSTORM_META {\n");
        fwrite($file, "\t\$map = [\n\t\t'' => '@',\n");

        foreach ($source as $key => $rawValue) {
            $parts = explode('&', $rawValue);
            $lastModule = end($parts);

            if (strpos($lastModule, '/') !== false) {
                $lastModule = basename($lastModule);
            }

            fwrite($file, "\t\t'{$key}' => $lastModule::class,\n");
        }

        fwrite($file, "\t];\n\n");

        foreach (self::MAP_TO as $map) {
            fwrite($file, "\toverride({$map}, map(\$map));\n");
        }

        fwrite($file, "}\n");
        fclose($file);
    }

}

(new PhpMetaGenerator($env, $useShopId, $shopId))->generate();

echo "\nDone.\n";
