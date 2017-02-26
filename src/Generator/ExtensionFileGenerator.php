<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator;

use Brzuchal\Compiler\Extension;

class ExtensionFileGenerator implements FileGenerator
{
    /**
     * @var Extension
     */
    private $extension;
    /**
     * @var FileGenerator[]
     */
    private $generators = [];
    private $minitStatements = [];

    public function __construct(Extension $extension)
    {
        $this->extension = $extension;
    }

    public function utilize(FileGenerator $generator)
    {
        $this->generators[] = $generator;
        if ($generator instanceof ClassFileGenerator) {
            $this->minitStatements[] = $generator->getRegisterFunction() . '();';
        }
    }

    /**
     * @return \SplFileObject[]
     * @throws \RuntimeException
     */
    public function generate() : array
    {
        $extensionName = $this->extension->getName();
        $buildDirectory = $this->extension->getBuildDirectory();
        $cFilePathname = $this->extension->getBuildDirectory() . DIRECTORY_SEPARATOR . "php_{$extensionName}.c";
        $hFilePathname = $this->extension->getBuildDirectory() . DIRECTORY_SEPARATOR . "php_{$extensionName}.h";
        if (!@\touch($cFilePathname) || !@\touch($hFilePathname)) {
            throw new \RuntimeException("Unable to create extension files: {$cFilePathname} and {$hFilePathname}");
        }
        $includeFiles = [];
        // register class function definition calls
        $registerFunctions = [];
        foreach ($this->generators as $generator) {
            $includeFiles = \array_merge($includeFiles, $generator->generate());
            if ($generator instanceof ClassFileGenerator) {
                $registerFunctions[] = $generator->getRegisterFunction() . '();';
            }
        }
        // #include statements
        $includeStatements = \implode(PHP_EOL, \array_map(function (\SplFileObject $fileObject) use ($buildDirectory) : string {
            return sprintf(
                '#include "%s"',
                \ltrim(str_replace($buildDirectory, '', $fileObject->getPathname()), DIRECTORY_SEPARATOR)
            );
        }, $includeFiles));
        $extensionVersion = $this->extension->getVersion();
        $uppercaseExtensionName = \strtoupper($extensionName);

        $registerFunctionStatements = \implode(PHP_EOL, $registerFunctions);

        $template = <<<EOF
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "main/php_ini.h"
#include "Zend/zend_operators.h"

#include "php_{$extensionName}.h"
{$includeStatements}

#if COMPILE_DL_{$uppercaseExtensionName}
ZEND_GET_MODULE({$extensionName})
#endif

static const zend_function_entry {$extensionName}_functions[] = {
//  PHP_FE(foo_hello, NULL)
  PHP_FE_END
};

zend_module_entry {$extensionName}_module_entry = {
  STANDARD_MODULE_HEADER,
  "{$extensionName}",
  {$extensionName}_functions,
  PHP_MINIT({$extensionName}), // module initialization
  PHP_MSHUTDOWN({$extensionName}), // module shutdown process
  PHP_RINIT({$extensionName}), // request initialization
  // PHP_RSHUTDOWN({$extensionName})  // reqeust shutdown process
  // PHP_MINFO({$extensionName}),              // providing module information
  "{$extensionVersion}",
  STANDARD_MODULE_PROPERTIES
};

PHP_MINIT_FUNCTION({$extensionName}) {
  {$registerFunctionStatements}
  return SUCCESS;
}
PHP_MSHUTDOWN_FUNCTION({$extensionName}) {
  return SUCCESS;
}

PHP_MINFO_FUNCTION({$extensionName}) {}

// Your functions here...
//PHP_FUNCTION(foo_hello) {
//  RETURN_TRUE;
//}
EOF;
        $cFile = new \SplFileObject($cFilePathname, 'w+');
        $cFile->fwrite($template);

$template= <<<EOF
#ifndef PHP_HELLO_H
#define PHP_HELLO_H 1

//PHP_FUNCTION(hello_world);

extern zend_module_entry {$extensionName}_module_entry;
#define {$extensionName}_module_ptr &{$extensionName}_module_entry

#endif
EOF;

        $hFile = new \SplFileObject($hFilePathname, 'w+');
        $hFile->fwrite($template);

        return \array_merge($includeFiles, [$cFile, $hFile]);
    }
}
