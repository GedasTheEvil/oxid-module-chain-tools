<?php

$env = $argv[1] ?? 'vagrant';
$shopId = $argv[2] ?? '1';
$noShopId = $argv[3] ?? '1';

/**
 * Used to transform older version of module chain file to the newer one with actual Name::class
 */
class StringToClassNameTransformer
{
    const TRANSFORMED_SUFFIX = 'fullModules.v2.';

    /**
     * @var string
     */
    private $confDir;

    /**
     * @var array
     */
    private $aModules = [];

    /**
     * @var int
     */
    private $shopId = 1;
    /**
     * @var bool
     */
    private $noShopId;

    /**
     * Transformer constructor.
     *
     * @param string $env
     * @param string $noShopId
     */
    public function __construct(string $env, string $noShopId)
    {
        $this->confDir = dirname(__FILE__, 2) . '/dev/config/' . $env . '/modules/';
        $this->noShopId = (bool)$noShopId;
    }

    /**
     * Save.
     *
     * @param string $fileName
     * @param int    $shopId
     *
     * @return void
     */
    public function save(string $fileName = '1.php', $shopId = 1)
    {
        $this->shopId = $shopId;
        include $this->confDir . $fileName;
        $converted = $this->confDir . self::TRANSFORMED_SUFFIX . $fileName;
        file_put_contents($converted, $this->wrapInArray($this->getTransformed()));
    }

    /**
     * Returns transformed.
     *
     * @return array
     */
    private function getTransformed()
    {
        return array_map(function ($a) {
            $chain = explode('&', $a);

            if (!is_array($chain)) {
                $chain = [$a];
            }

            return $chain;
        }, $this->noShopId ? $this->aModules : $this->aModules[$this->shopId]);
    }

    /**
     * Wrap in array.
     *
     * @param array $modules
     *
     * @return string
     */
    private function wrapInArray(array $modules)
    {
        $result = "<?php\n";
        $result .= "/*\n * Module Chain\n";
        $result .= " * Version 2\n */\n\n";
        $shopIdString = $this->noShopId ? '' : "[{$this->shopId}]";
        $result .= "\$this->aModules{$shopIdString} = array_map(function (\$a) {\n" .
            "    return implode('&', \$a);\n}, [\n";

        foreach ($modules as $mainModule => $extends) {
            $result .= "    {$this->getModuleCass($mainModule)} => [\n";

            foreach ($extends as $childModule) {
                $class = $this->getModuleCass($childModule);

                if ($class !== '') {
                    $result .= "        {$class},\n";
                }
            }

            $result .= "    ],\n";
        }

        return $result . "]);\n";
    }

    /**
     * Returns module cass.
     *
     * @param $mainModule
     *
     * @return string
     */
    private function getModuleCass($mainModule)
    {
        $mainModule = trim((string)$mainModule);

        if ($mainModule === '') {
            return '';
        }

        if (strpos($mainModule, '/') !== false) {
            return "'{$mainModule}'";
        }

        return "{$mainModule}::class";
    }
}

(new StringToClassNameTransformer($env, $noShopId))->save("{$shopId}.php", $shopId);
