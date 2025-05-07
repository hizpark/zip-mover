<?php

declare(strict_types=1);

namespace Hizpark\ZipMover\Tests;

use FilesystemIterator;
use Hizpark\ZipMover\Exception\ZipMoverException;
use Hizpark\ZipMover\ZipMover;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

final class ZipMoverTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/zip-mover-test-' . uniqid();
        mkdir($this->tempDir, 0o777, true);

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
        $zipMover = new ZipMover();
        $zipMover->compress("{$this->tempDir}/src");

        $zipFile = $this->getPrivateZipFile($zipMover);

        $this->assertFileExists($zipFile);
        $this->assertGreaterThan(0, filesize($zipFile));
    }

    public function testExtractCreatesExpectedFiles(): void
    {
        $zipMover = new ZipMover();
        $zipMover->compress("{$this->tempDir}/src");

        mkdir("{$this->tempDir}/extracted");
        $zipMover->extract("{$this->tempDir}/extracted");

        $this->assertFileExists("{$this->tempDir}/extracted/hello.txt");
        $this->assertFileExists("{$this->tempDir}/extracted/foo.md");
        $this->assertSame('Hello World', file_get_contents("{$this->tempDir}/extracted/hello.txt"));
    }

    public function testCleanDeletesTheFile(): void
    {
        $zipMover = new ZipMover();
        $zipMover->compress("{$this->tempDir}/src");

        $zipFile = $this->getPrivateZipFile($zipMover);

        $this->assertFileExists($zipFile);

        $zipMover->clean();

        $this->assertFileDoesNotExist($zipFile);
    }

    public function testExtractThrowsExceptionWhenZipFileIsModified(): void
    {
        $zipMover = new ZipMover();
        $zipMover->compress("{$this->tempDir}/src");

        $zipFile      = $this->getPrivateZipFile($zipMover);
        $originalHash = $zipMover->getHash();  // 原始哈希值，未篡改时

        // 模拟文件被篡改
        file_put_contents($zipFile, 'modified content');

        $this->expectException(ZipMoverException::class);
        $this->expectExceptionMessage('❌ ZIP 文件已被篡改，解压中止');

        // 调用 extract 方法，期待抛出异常
        $zipMover->extract("{$this->tempDir}/extracted");

        // 如果有需要，还可以做哈希校验
        $this->assertNotEquals($originalHash, $zipMover->getHash(), 'ZIP 文件内容应该已被篡改');
    }

    public function testExtractThrowsExceptionWhenHashIsNotSet(): void
    {
        $zipMover = new ZipMover();

        $this->expectException(ZipMoverException::class);
        $this->expectExceptionMessage('❌ 压缩操作未完成，无法校验哈希');

        $zipMover->extract("{$this->tempDir}/extracted");
    }

    private function getPrivateZipFile(ZipMover $zipMover): string
    {
        $reflection = new ReflectionClass($zipMover);
        $property   = $reflection->getProperty('zipFile');
        $property->setAccessible(true);

        $zipFile = $property->getValue($zipMover);

        if (!is_string($zipFile)) {
            throw new RuntimeException('The zipFile property is not a string');
        }

        return $zipFile;
    }
}
