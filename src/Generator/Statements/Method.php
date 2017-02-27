<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator\Statements;

use Brzuchal\Compiler\Util\StringUtil;
use PhpParser\Node\Stmt\ClassMethod;

class Method
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
     * @var MethodEntry
     */
    private $methodEntry;
    /**
     * @var MethodDeclaration
     */
    private $methodDeclaration;

    public function __construct(string $className, ClassMethod $classMethod)
    {
        $this->className = $className;
        $this->classMethod = $classMethod;
        $classNameSnake = StringUtil::createSnakeNameFromCamelCase($className);
        $this->argInfo = new ArgInfo("arginfo_{$classNameSnake}_{$classMethod->name}", $classMethod);
        $this->methodEntry = new MethodEntry($className, $classMethod, $this->argInfo);
        $this->methodDeclaration = new MethodDeclaration($className, $classMethod);
    }

    public function getArgInfo() : ArgInfo
    {
        return $this->argInfo;
    }

    public function getMethodEntry() : MethodEntry
    {
        return $this->methodEntry;
    }

    public function getMethodDeclaration() : MethodDeclaration
    {
        return $this->methodDeclaration;
    }
}
