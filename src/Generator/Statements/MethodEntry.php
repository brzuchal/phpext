<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator\Statements;

use PhpParser\Node\Stmt\ClassMethod;

class MethodEntry
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var ClassMethod
     */
    private $classMethod;
    /**
     * @var ArgInfo
     */
    private $argInfo;

    public function __construct(string $className, ClassMethod $classMethod, ArgInfo $argInfo)
    {
        $this->className = $className;
        $this->classMethod = $classMethod;
        $this->argInfo = $argInfo;
    }

    public function __toString() : string
    {
        $flags = [];
        if ($this->classMethod->name === '__construct') {
            $flags[] = 'ZEND_ACC_CTOR';
        }
        if ($this->classMethod->isPublic()) {
            $flags[] = 'ZEND_ACC_PUBLIC';
        }
        if ($this->classMethod->isProtected()) {
            $flags[] = 'ZEND_ACC_PROTECTED';
        }
        if ($this->classMethod->isPrivate()) {
            $flags[] = 'ZEND_ACC_PRIVATE';
        }
        return \sprintf(
            'PHP_ME(%s, %s, %s, %s)',
            $this->className,
            $this->classMethod->name,
            $this->argInfo->getName(),
            \implode(' | ', $flags)
        );

    }
}
