<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator;

use Brzuchal\Compiler\Extension;

class ConfigFileGenerator implements FileGenerator
{
    /**
     * @var \SplFileObject[]
     */
    private $buildFiles;
    /**
     * @var Extension
     */
    private $extension;

    public function __construct(array $buildFiles, Extension $extension)
    {
        $this->buildFiles = $buildFiles;
        $this->extension = $extension;
    }

    /**
     * @return \SplFileObject[]
     * @throws \RuntimeException
     */
    public function generate() : array
    {
        $extensionName = $this->extension->getName();
        $uppercaseExtensionName = \strtoupper($extensionName);
        $template = <<<EOF
PHP_ARG_ENABLE({$uppercaseExtensionName}, whether to enable {$extensionName} extension support, 
  [--enable-{$extensionName} Enable {$extensionName} extension support])

if test \$PHP_{$uppercaseExtensionName} != "no"; then
    AC_DEFINE(HAVE_{$uppercaseExtensionName}, 1, [{$extensionName}])
    PHP_NEW_EXTENSION({$extensionName}, php_{$extensionName}.c, \$ext_shared,, -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1)
fi
EOF;
        $configPathname = $this->extension->getBuildDirectory() . DIRECTORY_SEPARATOR . 'config.m4';
        if (!@\touch($configPathname)) {
            throw new \RuntimeException('Unable to create config.m4 file');
        }

        $configFile = new \SplFileObject($configPathname, 'w+');
        $configFile->fwrite($template);

        return [$configFile];
    }
}
