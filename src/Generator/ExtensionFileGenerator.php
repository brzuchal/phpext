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
        $registerDefinitionFunctionNames = [];
        foreach ($this->generators as $generator) {
            $includeFiles = \array_merge($includeFiles, $generator->generate());
            if ($generator instanceof ClassFileGenerator) {
                $registerDefinitionFunctionNames[] = $generator->getRegisterFunction() . '();';
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

        $registerDefinitionFunctionStatements = \implode(PHP_EOL, $registerDefinitionFunctionNames);

        $template = <<<EOF
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "main/php.h"
#include "main/php_ini.h"
#include "php_{$extensionName}.h"
{$includeStatements}

static const zend_function_entry {$extensionName}_functions[] = {
//  PHP_FE(foo_hello, NULL)
  PHP_FE_END
};

PHP_MINIT_FUNCTION({$extensionName}) {
  {$registerDefinitionFunctionStatements}
  return SUCCESS;
}
PHP_MSHUTDOWN_FUNCTION({$extensionName}) {
  return SUCCESS;
}

PHP_MINFO_FUNCTION({$extensionName}) {
    php_info_print_table_start();
    php_info_print_table_row(2, "{$extensionName} support", "enabled");
    php_info_print_table_row(2, "{$extensionName} version", PHP_{$uppercaseExtensionName}_VERSION);
    php_info_print_table_end();
}

zend_module_entry {$extensionName}_module_entry = {
  STANDARD_MODULE_HEADER_EX,
  NULL,
  NULL, // dependencies
  "{$extensionName}",
  {$extensionName}_functions,
  PHP_MINIT({$extensionName}), // module initialization
  PHP_MSHUTDOWN({$extensionName}), // module shutdown process
  NULL, // PHP_RINIT({$extensionName}), // request initialization
  NULL, // PHP_RSHUTDOWN({$extensionName})  // reqeust shutdown process
  PHP_MINFO({$extensionName}),              // providing module information
  PHP_{$uppercaseExtensionName}_VERSION,
  STANDARD_MODULE_PROPERTIES
};


#ifdef COMPILE_DL_{$uppercaseExtensionName}
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE();
#endif
ZEND_GET_MODULE({$extensionName})
#endif
EOF;
        $cFile = new \SplFileObject($cFilePathname, 'w+');
        $cFile->fwrite($template);

$template= <<<EOF
#ifndef PHP_{$uppercaseExtensionName}_H
#define PHP_{$uppercaseExtensionName}_H 1

#include "php.h"
#include "main/php.h"
// #include "zend_exceptions.h"
// #include "zend_interfaces.h"
// #include "zend_operators.h"
#include "ext/standard/info.h"
// #include "ext/standard/php_var.h"
// #include "ext/spl/spl_iterators.h"
// #include "ext/spl/spl_exceptions.h"
// #include "zend_smart_str.h"
// #include "ext/json/php_json.h"

extern zend_module_entry {$extensionName}_module_entry;
#define {$extensionName}_module_ptr &{$extensionName}_module_entry


/* Replace with version number for your extension */
#define PHP_{$uppercaseExtensionName}_VERSION "{$extensionVersion}"

#ifdef PHP_WIN32
#  define PHP_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#  define PHP_API __attribute__ ((visibility("default")))
#else
#  define PHP_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

// ZEND_BEGIN_MODULE_GLOBALS(console)
// zend_fcall_info        user_compare_fci;
// zend_fcall_info_cache  user_compare_fci_cache;
// ZEND_END_MODULE_GLOBALS(console)

#ifdef ZTS
#define DSG(v) TSRMG({$extensionName}_globals_id, zend_{$extensionName}_globals *, v)
#else
#define DSG(v) ({$extensionName}_globals.v)
#endif

// ZEND_EXTERN_MODULE_GLOBALS({$extensionName});

#if defined(ZTS) && defined(COMPILE_DL_{$uppercaseExtensionName})
ZEND_TSRMLS_CACHE_EXTERN();
#endif

#endif
EOF;

        $hFile = new \SplFileObject($hFilePathname, 'w+');
        $hFile->fwrite($template);

        return \array_merge($includeFiles, [$cFile, $hFile]);
    }
}
