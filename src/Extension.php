<?php declare(strict_types=1);
namespace Brzuchal\Compiler;

class Extension
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $version;
    /**
     * @var string
     */
    private $sourceDirectory;
    /**
     * @var string
     */
    private $buildDirectory;

    public function __construct(
        string $name,
        string $version,
        string $sourceDirectory,
        string $buildDirectory
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->sourceDirectory = $sourceDirectory;
        $this->buildDirectory = $buildDirectory . DIRECTORY_SEPARATOR . $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getVersion() : string
    {
        return $this->version;
    }

    public function getSourceDirectory() : string
    {
        return $this->sourceDirectory;
    }

    public function getBuildDirectory() : string
    {
        return $this->buildDirectory;
    }

    /**
     * @return \SplFileInfo[]|\Traversable
     */
    public function getSources() : \Traversable
    {
        return new FilesystemFinder($this->sourceDirectory, 'php');
    }

    public function clearBuildDirectory() : void
    {
        if (!@\mkdir($this->buildDirectory, 0777, true) && !\is_dir($this->buildDirectory)) {
            throw new \RuntimeException("Unable to create build directory: {$this->buildDirectory}");
        }
        foreach (new FilesystemFinder($this->buildDirectory) as $file) {
            @\unlink($file->getPath());
        }
    }


}
