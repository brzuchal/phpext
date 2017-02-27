<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator;

use Brzuchal\Compiler\Extension;
use Brzuchal\Compiler\Generator\Statements\Method;
use Brzuchal\Compiler\Util\StringUtil;
use PhpParser\Node\Stmt\Class_;

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

    public function getRegisterFunction() : string
    {
        return \sprintf("php_register_%s_definition", StringUtil::createSnakeNameFromCamelCase($this->getClassName()));
    }

    /**
     * @return \SplFileObject[]
     * @throws \RuntimeException
     */
    public function generate() : array
    {
        $className = $this->getClassName();
        $filePathname = $this->extension->getBuildDirectory() . DIRECTORY_SEPARATOR . 'class_' . StringUtil::createSnakeNameFromCamelCase($className) . '.c';
        if (!@\touch($filePathname)) {
            throw new \RuntimeException("Unable to create source file for class {$className}");
        }

        $cFile = new \SplFileObject($filePathname, 'w+');
        $cFile->fwrite($this->generateRegisterFunction());

        return [$className => $cFile];
    }

    private function generateRegisterFunction() : string
    {
        $className = $this->class->name;
        $namespaceName = \trim(\substr($this->getClassName(), 0, -\strlen($className)), '\\');
        $classNameSnake = StringUtil::createSnakeNameFromCamelCase($this->getClassName());

        $methodArgInfo = '';
        $methodEntries = '';
        $methodDeclarations = '';
        foreach ($this->class->getMethods() as $classMethod) {
            $method = new Method($className, $classMethod);
            $methodArgInfo .= (string)$method->getArgInfo() . PHP_EOL;
            $methodEntries .= "\t\t" . (string)$method->getMethodEntry() . PHP_EOL;
            $methodDeclarations .= (string)$method->getMethodDeclaration() . PHP_EOL;
        }
        // just for pretty print
        $methodArgInfo = rtrim($methodArgInfo, PHP_EOL);
        $methodEntries = rtrim($methodEntries, PHP_EOL);
        $methodDeclarations = rtrim($methodDeclarations, PHP_EOL);
        $template = <<<EOF
#include "php.h"

zend_class_entry *{$classNameSnake}_definition_ce;

{$methodArgInfo}

{$methodDeclarations}

void {$this->getRegisterFunction()}()
{
    zend_class_entry ce;
    zend_function_entry methods[] = {
{$methodEntries}        
        PHP_FE_END
    };
    INIT_NS_CLASS_ENTRY(ce, "{$namespaceName}", "{$className}", methods);
    {$classNameSnake}_definition_ce = zend_register_internal_class(&ce);
}
EOF;
//    zend_declare_property_null({$classNameSnake}_definition_ce, "definitions", sizeof("definitions")-1, ZEND_ACC_PROTECTED);
        return $template;
    }
}
