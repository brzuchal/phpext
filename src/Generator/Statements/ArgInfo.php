<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator\Statements;

use Brzuchal\Compiler\Util\TypeUtil;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;

class ArgInfo
{
    /**
     * @var string
     */
    private $name;
    private $returnType;
    private $returnByRef = false;
    /**
     * @var array
     */
    private $params;

    public function __construct(string $name, FunctionLike $functionLike)
    {
        $this->name = $name;
        $this->returnType = $functionLike->getReturnType();
        $this->returnByRef = $functionLike->returnsByRef();
        $this->params = $functionLike->getParams();
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function __toString() : string
    {
        $returnByRef = (int)$this->returnByRef;
        $paramsDefinition = [];
        $requiredNumArgs = 0;
        foreach ($this->params as $param) {
            $passByRef = (int)$param->byRef;
            $type = $param->type;
            $allowNull = 0;
            if ($type instanceof NullableType) {
                $allowNull = 1;
                $type = $type->type;
            }
            if (null !== $param->default || !($type instanceof NullableType)) {
                $requiredNumArgs++;
            }
            if ($type instanceof FullyQualified) {
                if ($param->variadic) {
                    // ZEND_ARG_VARIADIC_OBJ_INFO(pass_by_ref, className, classname, allow_null)
                    $paramsDefinition[] = "ZEND_ARG_VARIADIC_OBJ_INFO({$passByRef}, {$param->name}, \"{$param->type}\", {$allowNull})";
                } else {
                    // ZEND_ARG_OBJ_INFO(pass_by_ref, className, classname, allow_null)
                    $paramsDefinition[] = "ZEND_ARG_OBJ_INFO({$passByRef}, {$param->name}, \"{$param->type}\", {$allowNull})";
                }
            } elseif(\is_string($type)) {
                $typeDeclaration = TypeUtil::getInternalType($type);
                if ($param->variadic) {
                    // ZEND_ARG_VARIADIC_TYPE_INFO(pass_by_ref, className, type_hint, allow_null)
                    $paramsDefinition[] = "ZEND_ARG_VARIADIC_TYPE_INFO({$passByRef}, {$param->name}, {$typeDeclaration}, {$allowNull})";
                } else {
                    // ZEND_ARG_TYPE_INFO(pass_by_ref, className, type_hint, allow_null)
                    $paramsDefinition[] = "ZEND_ARG_TYPE_INFO({$passByRef}, {$param->name}, {$typeDeclaration}, {$allowNull})";
                }
            } else {
                if ($param->variadic) {
                    // ZEND_ARG_VARIADIC_INFO(pass_by_ref, className)
                    $paramsDefinition[] = "ZEND_ARG_VARIADIC_TYPE_INFO({$passByRef}, {$param->name})";
                } else {
                    // ZEND_ARG_INFO(pass_by_ref, className)
                    $paramsDefinition[] = "ZEND_ARG_INFO({$passByRef}, {$param->name})";
                }
            }
        }
        $paramsDefinitionDeclaration = "\t" . \implode(PHP_EOL . "\t", $paramsDefinition);
        if ($this->returnType) {
            $allowNull = 0;
            $returnType = $this->returnType;
            if ($returnType instanceof NullableType) {
                $allowNull = 1;
                $returnType = $returnType->type;
            }
            if ($returnType instanceof FullyQualified) {
                // ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(className, return_reference, required_num_args, classname, allow_null)
                $beginArgInfo = \sprintf(
                    'ZEND_BEGIN_ARG_WITH_RETURN_OBJ_INFO_EX(%s, %d, %d, "%s", %d)',
                    $this->name,
                    $returnByRef,
                    $requiredNumArgs,
                    (string)$returnType,
                    $allowNull
                );
            } else {
                // ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(className, return_reference, required_num_args, type, allow_null)
                $beginArgInfo = \sprintf(
                    'ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(%s, %d, %d, %s, %d, %d)',
                    $this->name,
                    $returnByRef,
                    $requiredNumArgs,
                    TypeUtil::getInternalType($returnType),
                    (string)$returnType,
                    $allowNull
                );
            }
        } else {
            // ZEND_BEGIN_ARG_INFO_EX(className, _unused, return_reference, required_num_args)
            $beginArgInfo = \sprintf(
                "ZEND_BEGIN_ARG_INFO_EX({$this->name}, 0, %d, %d)",
                $returnByRef,
                $requiredNumArgs
            );
        }
        $template = <<<EOF
// {$this->name}
{$beginArgInfo}
{$paramsDefinitionDeclaration}
ZEND_END_ARG_INFO()

EOF;
        return $template;
    }
}
