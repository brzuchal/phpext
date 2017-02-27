<?php declare(strict_types=1);
namespace Brzuchal\Compiler\Util;

final class FilesystemUtil
{
    public static function createTargetFromSourceFileInfo(string $buildDirectory, \SplFileInfo $fileInfo) : \SplFileInfo
    {
        return new \SplFileInfo(implode(
                DIRECTORY_SEPARATOR,
                \array_map(
                    StringUtil::class . '::createSnakeNameFromCamelCase',
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
}
