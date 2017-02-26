<?php
namespace Brzuchal\CompilerTest;

use Brzuchal\Compiler\FilesystemFinder;
use PHPUnit\Framework\TestCase;

class FilesystemFinderTest extends TestCase
{
    public function testFindPHPFiles()
    {
        $finder = new FilesystemFinder(__DIR__, 'php');
        $this->assertArrayHasKey(__FILE__, \iterator_to_array($finder));
    }
}
