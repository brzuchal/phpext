<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator\Statements;

use PhpParser\Node\Stmt\ClassMethod;

class MethodDeclaration
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var ClassMethod
     */
    private $classMethod;

    public function __construct(string $className, ClassMethod $classMethod)
    {
        $this->className = $className;
        $this->classMethod = $classMethod;
    }

    public function __toString() : string
    {
        $classNameSymbol = \str_replace('\\', '', $this->className);

        $template = <<<EOF
/* {{{ proto void {$classNameSymbol}::{$this->classMethod->name}(array \$definitions)
   Create the {$classNameSymbol} object */
ZEND_METHOD({$classNameSymbol}, {$this->classMethod->name})
{
}
/* }}} */

EOF;
        return $template;
    }
}
