<?php

declare(strict_types=1);

namespace Hizpark\ZipMover;

use FilesystemIterator;
use Hizpark\ZipMover\Exception\ZipMoverException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

/**
 * ZipFileHandler 类用于处理 ZIP 文件的压缩与解压操作
 * 提供了压缩文件夹为 ZIP 文件、解压 ZIP 文件以及删除 ZIP 文件的功能
 */
class ZipMover
{
    private string $zipFile;

    private ?string $hash = null; // 用于存储压缩后计算的 hash 值

    public function __construct()
    {
        $this->zipFile = sys_get_temp_dir() . '/zip-mover-' . uniqid() . '.zip';
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * 将指定文件夹压缩成 ZIP 文件
     *
     * @param string $srcPath 源文件夹路径
     *
     * @throws ZipMoverException 如果源文件夹不存在或不是有效的目录，或压缩过程中出现错误
     */
    public function compress(string $srcPath): void
    {
        // 检查目录是否存在
        if (!file_exists($srcPath)) {
            throw new ZipMoverException("❌ 源路径不存在: $srcPath");
        }

        // 检查是否为有效目录
        if (!is_dir($srcPath)) {
            throw new ZipMoverException("❌ 源路径不是有效的目录: $srcPath");
        }

        $zip = new ZipArchive();

        // 打开（创建或覆盖）ZIP文件
        $result = $zip->open($this->zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new ZipMoverException("❌ 打包失败，状态码: $result");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue; // 或 throw new \ZipMoverException('意外的文件类型');
            }

            if (!$file->isDir()) {
                $relativePath = ltrim(substr($file->getPathname(), strlen($srcPath)), '/\\');

                if (!$zip->addFile($file->getPathname(), $relativePath)) {
                    throw new ZipMoverException("❌ 打包失败，添加文件失败: {$file->getPathname()}");
                }
            }
        }

        // 确保ZIP文件已经成功生成
        if (!$zip->close()) {
            throw new ZipMoverException("❌ 打包失败，写入 ZIP 文件失败: $this->zipFile");
        }

        // 计算并保存 ZIP 文件的哈希值
        $this->hash = hash_file('sha256', $this->zipFile) ?: null;
    }

    /**
     * 从指定 ZIP 文件解压内容到目标路径
     *
     * @param string $destPath 解压目标路径
     *
     * @throws ZipMoverException 如果目标路径无效或解压过程中发生错误
     */
    public function extract(string $destPath): void
    {
        // 校验哈希值是否存在，防止解压未进行压缩
        if ($this->hash === null) {
            throw new ZipMoverException('❌ 压缩操作未完成，无法校验哈希');
        }

        // 校验当前 zip 文件的哈希值
        $currentHash = hash_file('sha256', $this->zipFile);

        if ($currentHash !== $this->hash) {
            throw new ZipMoverException('❌ ZIP 文件已被篡改，解压中止');
        }

        // 检查目录是否存在
        if (!file_exists($destPath)) {
            throw new ZipMoverException("❌ 目标路径不存在: $destPath");
        }

        // 检查是否为有效目录
        if (!is_dir($destPath)) {
            throw new ZipMoverException("❌ 目标路径不是有效的目录: $destPath");
        }

        if (!file_exists($this->zipFile) || !is_readable($this->zipFile)) {
            throw new ZipMoverException("❌ ZIP 文件不存在或不可读: {$this->zipFile}");
        }

        $zip = new ZipArchive();

        // 打开ZIP文件
        if ($zip->open($this->zipFile) !== true) {
            throw new ZipMoverException('❌ 解压失败，错误信息: ' . $zip->getStatusString());
        }

        // 解压到目标路径
        if (!$zip->extractTo($destPath)) {
            throw new ZipMoverException('❌ 解压失败，错误信息: ' . $zip->getStatusString());
        }

        // 释放资源
        $zip->close();
    }

    /**
     * 清理临时文件
     *
     * @throws ZipMoverException
     */
    public function clean(): void
    {
        // 确保文件存在
        if (file_exists($this->zipFile)) {
            // 删除文件
            if (!unlink($this->zipFile)) {
                throw new ZipMoverException("❌ 删除 ZIP 文件失败: $this->zipFile");
            }
        }
    }
}
