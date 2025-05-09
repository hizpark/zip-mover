# Zip Mover

> Simple and reliable PHP utility for compressing and extracting a ZIP file

![License](https://img.shields.io/github/license/hizpark/zip-mover?style=flat-square)
![Latest Version](https://img.shields.io/packagist/v/hizpark/zip-mover?style=flat-square)
![PHP Version](https://img.shields.io/badge/php-8.2--8.4-blue?style=flat-square)
![Static Analysis](https://img.shields.io/badge/static_analysis-PHPStan-blue?style=flat-square)
![Tests](https://img.shields.io/badge/tests-PHPUnit-brightgreen?style=flat-square)
[![codecov](https://codecov.io/gh/hizpark/zip-mover/branch/main/graph/badge.svg)](https://codecov.io/gh/hizpark/zip-mover)
![CI](https://github.com/hizpark/zip-mover/actions/workflows/ci.yml/badge.svg?style=flat-square)

Lightweight PHP library for easily compressing the contents of a directory into a ZIP archive and extracting them. Provides a clean, intuitive API for efficient file packaging workflows.

## 📦 安装

```bash
composer require hizpark/zip-mover
```

## 📂 目录结构

```txt
src
├── Exception
│   └── ZipMoverException.php
└── ZipMover.php
```

## 🚀 用法示例

### 示例 1：压缩目录为 ZIP 文件

```php
use Hizpark\ZipMover\ZipMover;

$mover = new ZipMover();
$mover->compress('/path/to/source-dir');
```

### 示例 2：解压 ZIP 文件到指定目录

```php
use Hizpark\ZipMover\ZipMover;

$mover = new ZipMover();
$mover->extract('/path/to/destination-dir');
```

## 📐 接口说明

### ZipMover::compress(string $srcPath): void

> 将指定目录压缩为 ZIP 文件

```php
public function compress(string $srcPath): void;
```

### ZipMover::extract(string $destPath): void

> 将 ZIP 文件内容解压到指定目录

```php
public function extract(string $destPath): void;
```

### ZipMover::clean(): void

> 清理临时文件

```php
public function clean(): void;
```

## 🔍 静态分析

使用 PHPStan 工具进行静态分析，确保代码的质量和一致性：

```bash
composer stan
```

## 🎯 代码风格

使用 PHP-CS-Fixer 工具检查代码风格：

```bash
composer cs:chk
```

使用 PHP-CS-Fixer 工具自动修复代码风格问题：

```bash
composer cs:fix
```

## ✅ 单元测试

执行 PHPUnit 单元测试：

```bash
composer test
```

执行 PHPUnit 单元测试并生成代码覆盖率报告：

```bash
composer test:coverage
```

## 🤝 贡献指南

欢迎 Issue 与 PR，建议遵循以下流程：

1. Fork 仓库
2. 创建新分支进行开发
3. 提交 PR 前请确保测试通过、风格一致
4. 提交详细描述

## 📝 License

MIT License. See the [LICENSE](LICENSE) file for details.
