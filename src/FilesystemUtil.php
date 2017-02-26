<?php declare(strict_types=1);
namespace Brzuchal\Compiler;

final class FilesystemUtil
{
    public static function createTargetFromSourceFileInfo(string $buildDirectory, \SplFileInfo $fileInfo) : \SplFileInfo
    {
        return new \SplFileInfo(implode(
                DIRECTORY_SEPARATOR,
                \array_map(
                    'self::createSnakeNameFromCamelCase',
                    \explode(
                        DIRECTORY_SEPARATOR,
                        \ltrim(
                            \str_replace($buildDirectory, '', \str_replace(".{$fileInfo->getExtension()}", '', $fileInfo->getPathname())),
                            DIRECTORY_SEPARATOR
                        )
                    )
                )
            ) . '.c');
    }

    public static function createSnakeNameFromCamelCase(string $input) : string
    {
        \preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === \strtoupper($match) ? \strtolower($match) : \lcfirst($match);
        }

        return \implode('_', $ret);
    }
}
