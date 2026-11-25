<?php
declare(strict_types=1);

interface ImageStorage
{
    /** @return string public URL path */
    public function put(string $contents, string $extension): string;
}
