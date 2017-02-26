<?php declare(strict_types=1);
namespace Brzuchal\Compiler;

class FilesystemFinder implements \IteratorAggregate
{
    /**
     * @var string
     */
    private $directory;
    /**
     * @var null|string
     */
    private $extensions;

    public function __construct(string $directory, ?string ...$extensions)
    {
        $this->directory = $directory;
        $this->extensions = $extensions;
    }

    /**
     * @return \SplFileInfo[]|\Traversable
     */
    public function getIterator() : \Traversable
    {
        $recursiveDirectoryIterator = new \RecursiveDirectoryIterator($this->directory);
        $filterIterator = new \RecursiveCallbackFilterIterator($recursiveDirectoryIterator, function (\SplFileInfo $fileInfo) : bool {
            return $this->filterCallback($fileInfo);
        });

        return $filterIterator;
    }

    protected function filterCallback(\SplFileInfo $fileInfo) : bool
    {
        if ($fileInfo->isDir() && \in_array($fileInfo->getPath(), ['.', '..'])) {
            return false;
        }
        if (null === $this->extensions) {
            return true;
        }

        return \in_array($fileInfo->getExtension(), $this->extensions);
    }
}
