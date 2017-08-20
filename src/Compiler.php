<?php
namespace Brzuchal\Compiler;


use Brzuchal\Compiler\Generator\ClassFileGenerator;
use Brzuchal\Compiler\Generator\ConfigFileGenerator;
use Brzuchal\Compiler\Generator\ExtensionFileGenerator;
use PhpParser\Error;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;

class Compiler
{
    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var NodeFinder
     */
    private $nodeFinder;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
        $this->nodeFinder = new NodeFinder();
    }

    public function compile(Extension $extension) : bool
    {
        $extension->clearBuildDirectory();
        $generatedFiles = [];

        $extensionGenerator = new ExtensionFileGenerator($extension);

        foreach ($extension->getSources() as $fileInfo) {
            echo "\033[0;32mProcessing file: \033[0;36m{$fileInfo->getPathname()}...\033[0m\n";
            $stmts = $this->parseFile($fileInfo);
            foreach ($this->nodeFinder->findNamespaces(...$stmts) as $namespace) {
                /** @var Class_[] $classes */
                $classes = $this->nodeFinder->findClasses(...$namespace->stmts);
                foreach ($classes as $class) {
                    $extensionGenerator->utilize(new ClassFileGenerator($class, $extension));
//                    $files = (new ClassFileGenerator($class, $extension))->generate();
//                    foreach ($files as $className => $fileObject) {
//                        $extensionGenerator->registerClass($className, $fileObject->getPathname());
//                    }
                }
            }

//            $targetFileInfo = $this->createTargetFromSourceFileInfo($buildDirectory, $fileInfo);
//
//            if ($this->p($fileInfo, $targetFileInfo)) {
//                $generatedFiles[] = $targetFileInfo;
//            }
        }
        $files = $extensionGenerator->generate();
        $configFileGenerator = new ConfigFileGenerator($files, $extension);
        $configFileGenerator->generate();
//        $this->generateConfig($buildDirectory, $extension->getName(), $generatedFiles);
//        \var_dump($generatedFiles);
//dump($files);
        return true;
    }

    private function parseFile(\SplFileInfo $fileInfo) : array
    {
        $stmts = [];
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver); // we will need resolved names
//        $traverser->addVisitor(new NamespaceConverter); // our own node visitor
        try {
            $nodes = $this->parser->parse(\file_get_contents($fileInfo->getPathname()));
            $stmts = $traverser->traverse($nodes);
        } catch (Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }
        return $stmts;
    }
}
