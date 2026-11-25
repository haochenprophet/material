<?php
declare(strict_types=1);

interface Storage
{
    /** 写入内容，返回不可猜测的 key；不覆盖已有文件。 */
    public function put(string $contents, string $extension = 'csv'): string;
}
