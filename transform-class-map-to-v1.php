<?php

$env = $argv[1] ?? 'vagrant';
$shopId = $argv[2] ?? '1';
$noShopId = $argv[3] ?? '1';

/**
 * Used to transform older version of module chain file to the newer one with actual Name::class
 */
class ClassNameToStringTransformer
{
    const TRANSFORMED_SUFFIX = 'fullModules.v2.';

    /**
     * @var string
     */
    private $confDir;

    /**
     * @var array|string[][]
     */
    private $aModules = [];

    /**
     * @var int
     */
    private $shopId;
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
     * @throws \RuntimeException
     */
    public function save(string $fileName = '1.php', int $shopId = 1)
    {
        $this->shopId = $shopId;
        $source = $this->confDir . self::TRANSFORMED_SUFFIX . $fileName;

        if (!file_exists($source)) {
            throw new RuntimeException("Target file '{$source}' not found!");
        }

        include $source;
        $converted = $this->confDir . $fileName;
        file_put_contents(
            $converted,
            $this->wrapInArray(
                $this->noShopId
                    ? $this->aModules
                    : $this->aModules[$this->shopId]
            )
        );
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
        $maxWhiteSpace = $this->getMaxWhiteSpace();
        $result = "<?php\n";
        $result .= "/*\n * Module Chain\n";
        $result .= " * @generated with 'bin/transform-class-map-to-v1.php' \n";
        $result .= " * Version 1\n */\n\n";
        $shopIdString = $this->noShopId ? '' : "[{$this->shopId}]";
        $result .= "\$this->aModules{$shopIdString} = [\n";

        foreach ($modules as $mainModule => $extends) {
            $whiteSpace = $maxWhiteSpace - strlen($mainModule);
            $result .= "    '{$mainModule}' {$this->getSpaces($whiteSpace)}=> '{$extends}',\n";
        }

        return $result . "];\n";
    }

    /**
     * Returns max white space.
     *
     * @return int
     */
    private function getMaxWhiteSpace()
    {
        $max = 0;
        $modules = $this->noShopId
            ? $this->aModules
            : $this->aModules[$this->shopId];

        foreach ($modules as $key => $ignore) {
            $max = max($max, strlen($key));
        }

        return $max;
    }

    /**
     * Returns spaces.
     *
     * @param int $max
     *
     * @return string
     */
    private function getSpaces($max)
    {
        $spaces = '';

        for ($i = 0; $i < $max; ++$i) {
            $spaces .= ' ';
        }

        return $spaces;
    }
}

(new ClassNameToStringTransformer($env, $noShopId))->save("{$shopId}.php", $shopId);
