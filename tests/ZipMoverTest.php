<?php

declare(strict_types=1);

namespace Hizpark\ZipMover\Tests;

use FilesystemIterator;
use Hizpark\ZipMover\Exception\ZipMoverException;
use Hizpark\ZipMover\ZipMover;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ZipMoverTest extends TestCase
{
    private string $tempDir;

    private string $zipFile;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/zip_mover_test_' . uniqid();
        mkdir($this->tempDir, 0o777, true);

        $this->zipFile = $this->tempDir . '/test.zip';

        // 创建一个测试文件夹结构
        mkdir("{$this->tempDir}/src");
        file_put_contents("{$this->tempDir}/src/hello.txt", 'Hello World');
        file_put_contents("{$this->tempDir}/src/foo.md", 'Foo Bar');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it    = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    public function testCompressCreatesZipFile(): void
    {
        $zipMover = new ZipMover($this->zipFile);
        $zipMover->compress("{$this->tempDir}/src");

        $this->assertFileExists($this->zipFile);
        $this->assertGreaterThan(0, filesize($this->zipFile));
    }

    public function testExtractCreatesExpectedFiles(): void
    {
        $zipMover = new ZipMover($this->zipFile);
        $zipMover->compress("{$this->tempDir}/src");

        mkdir("{$this->tempDir}/extracted");
        $zipMover->extract("{$this->tempDir}/extracted");

        $this->assertFileExists("{$this->tempDir}/extracted/hello.txt");
        $this->assertFileExists("{$this->tempDir}/extracted/foo.md");
        $this->assertSame('Hello World', file_get_contents("{$this->tempDir}/extracted/hello.txt"));
    }

    public function testRemoveZipFileDeletesTheFile(): void
    {
        $zipMover = new ZipMover($this->zipFile);
        $zipMover->compress("{$this->tempDir}/src");

        $this->assertFileExists($this->zipFile);

        $zipMover->removeZipFile();

        $this->assertFileDoesNotExist($this->zipFile);
    }

    // 测试哈希校验
    public function testExtractThrowsExceptionWhenZipFileIsModified(): void
    {
        $zipMover = new ZipMover($this->zipFile);
        $zipMover->compress("{$this->tempDir}/src");

        // 获取原始哈希值
        $originalHash = $zipMover->getHash();

        // 模拟文件被篡改
        file_put_contents($this->zipFile, 'modified content');

        $this->expectException(ZipMoverException::class);
        $this->expectExceptionMessage('❌ ZIP 文件已被篡改，解压中止');

        $zipMover->extract("{$this->tempDir}/extracted");

        // 恢复原文件内容
        file_put_contents($this->zipFile, $originalHash);
    }

    // 测试压缩后未进行解压的哈希校验
    public function testExtractThrowsExceptionWhenHashIsNotSet(): void
    {
        $zipMover = new ZipMover($this->zipFile);

        // 压缩文件后不进行解压，验证哈希
        $this->expectException(ZipMoverException::class);
        $this->expectExceptionMessage('❌ 压缩操作未完成，无法校验哈希');

        $zipMover->extract("{$this->tempDir}/extracted");
    }
}
