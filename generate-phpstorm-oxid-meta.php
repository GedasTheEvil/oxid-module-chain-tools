<?php

$env = $argv[1] ?? 'vagrant';
$useShopId = $argv[2] ?? 0;
$shopId = $argv[3] ?? 1;

define('CUSTOM_CONFIG_PATH', __DIR__ . '/dev/config/');
define('SHOP_ENV_LOCAL', 'local');

class PhpMetaGenerator
{
    const MAP_TO = [
        '\oxNew(0)',
        '\twtNew(0)',
        '\oxRegistry::get(0)',
        '\oxUtilsObject::oxNew(0)',
    ];

    const KNOWN_WORDS = [
        'ox',
        'list',
        'payment',
        'article',
        'order',
        'input',
        'validator',
        'picture',
        'search',
        'utils',
        'view',
        'view',
        'voucher',
        'service',
        'file',
        'config',
        'encoder',
        'decoder',
        'variant',
        'simple',
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

    public function __construct(string $env, int $useShopId = 1, int $shopId = 1)
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

    private function dump(string $outputFile)
    {
        $source = $this->useShopId ? $this->aModules[$this->shopId] : $this->aModules;
        $file = fopen($outputFile, 'wb');
        fwrite($file, "<?php\n\nnamespace PHPSTORM_META {\n");
        fwrite($file, "    \$map = [\n        '' => '@',\n");

        foreach ($source as $key => $rawValue) {
            $parts = explode('&', $rawValue);
            $lastModule = end($parts);

            if (strpos($lastModule, '/') !== false) {
                $lastModule = basename($lastModule);
            }

            foreach ($this->moduleCases($key) as $moduleName) {
                fwrite($file, "        '{$moduleName}' => {$lastModule}::class,\n");
            }
        }

        fwrite($file, "    ];\n\n");

        foreach (self::MAP_TO as $map) {
            fwrite($file, "    override({$map}, map(\$map));\n");
        }

        fwrite($file, "}\n");
        fclose($file);
    }

    private function moduleCases(string $moduleName): array
    {
        $nameLength = strlen($moduleName);
        $cases = [$moduleName];

        foreach (self::KNOWN_WORDS as $word) {
            $pos = stripos($moduleName, $word);
            $length = strlen($word);
            $index1 = $pos + $length;

            if ($pos !== false) {
                if ($word !== 'ox') {
                    $moduleName[$pos] = strtoupper($moduleName[$pos]);
                    $cases[] = $moduleName;
                }

                if ($index1 < $nameLength) {
                    $moduleName[$index1] = strtoupper($moduleName[$index1]);
                    $cases[] = $moduleName;
                }
            }
        }

        return array_unique($cases);
    }
}

(new PhpMetaGenerator($env, (int)$useShopId, (int)$shopId))->generate();

echo "\nDone.\n";
