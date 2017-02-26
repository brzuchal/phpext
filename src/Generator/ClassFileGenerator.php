<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator;

use Brzuchal\Compiler\Extension;
use Brzuchal\Compiler\FilesystemUtil;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class ClassFileGenerator implements FileGenerator
{
    /**
     * @var Class_
     */
    private $class;
    /**
     * @var Extension
     */
    private $extension;

    public function __construct(Class_ $class, Extension $extension)
    {
        $this->class = $class;
        $this->extension = $extension;
    }

    public function getClassName() : string
    {
        return \property_exists($this->class, 'namespacedName') ?
            $this->class->namespacedName->toString() :
            $this->class->name;
    }

    public function getClassNameSymbol() : string
    {
        return \str_replace('\\', '', $this->getClassName());
    }

    public function getRegisterFunction() : string
    {
        return \sprintf("php_register_%s_definition", FilesystemUtil::createSnakeNameFromCamelCase($this->getClassName()));
    }

    /**
     * @return \SplFileObject[]
     * @throws \RuntimeException
     */
    public function generate() : array
    {
        $className = $this->getClassName();
        $filePathname = $this->extension->getBuildDirectory() . DIRECTORY_SEPARATOR . FilesystemUtil::createSnakeNameFromCamelCase($className) . '.c';
        if (!@\touch($filePathname)) {
            throw new \RuntimeException("Unable to create source file for class {$className}");
        }

        $cFile = new \SplFileObject($filePathname, 'w+');
        $cFile->fwrite($this->generateRegisterFunction());

        return [$className => $cFile];
    }

    private function getMethodEntries() : array
    {
        return array_map(function (ClassMethod $classMethod) : string {
            return \sprintf(
                "PHP_ME(%s, %s, %s, %s)",
                $this->getClassNameSymbol(),
                $classMethod->name,
                "arginfo_{$this->getClassNameSymbol()}_{$classMethod->name}",
                $classMethod->flags
            );
        }, $this->class->getMethods());
    }

    private function generateRegisterFunction() : string
    {
        $className = $this->class->name;
        $namespaceName = \trim(\substr($this->getClassName(), 0, -\strlen($className)), '\\');
        $classNameSymbol = $this->getClassNameSymbol();
        $methodEntries = \implode(PHP_EOL, $this->getMethodEntries());
//        dump($className, $namespaceName, $classNameSymbol, $methodEntries);
        $template = <<<EOF
#include "php.h"

zend_class_entry *{$classNameSymbol}_definition_ce;

/* {{{ proto void {$classNameSymbol}::__construct(array \$definitions)
   Create the {$classNameSymbol} object */
//ZEND_METHOD({$classNameSymbol}, __construct)
//{
//}
/* }}} */


void {$this->getRegisterFunction()}()
{
    zend_class_entry ce;
    zend_function_entry methods[] = {
        {$methodEntries}        
        PHP_FE_END
    };
    INIT_NS_CLASS_ENTRY(ce, "{$namespaceName}", "{$classNameSymbol}", methods);
    {$classNameSymbol}_definition_ce = zend_register_internal_class(&ce);
//    zend_declare_property_null({$classNameSymbol}_definition_ce, "definitions", sizeof("definitions")-1, ZEND_ACC_PROTECTED);
}
EOF;
dump($template);
        return $template;
    }
}
