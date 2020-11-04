<?php

declare(strict_types=1);

namespace Artemeon\Composer\Exception;

use RuntimeException;

final class UnableToLoadModulePackageException extends RuntimeException
{
    private function __construct(string $path)
    {
        parent::__construct('unable to load module package at "' . $path . '"');
    }

    public static function at(string $path): self
    {
        return new self($path);
    }
}
