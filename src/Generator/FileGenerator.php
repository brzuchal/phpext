<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Generator;

interface FileGenerator
{
    /**
     * @return \SplFileObject[]
     */
    public function generate() : array;
}
