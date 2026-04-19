<?php

namespace App\Support;

class AttachmentPathValidator
{
    public function isSafeRelativePath(string $path): bool
    {
        return ! str_starts_with($path, '/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '://')
            && preg_match('/^[a-zA-Z]:[\\\\\\/]/', $path) !== 1;
    }
}
