<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Entities\ShortUrl;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Connect DB
try {
    $dsn = "{$_ENV['DB_CONNECTION']}:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}";
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    // 设置 PDO 错误模式，用于抛出异常
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('数据库连接失败：' . $e->getMessage());
}

// 转换短网址
$shortUrl = ShortUrl::new($pdo, 'http://www.baidu.com');

echo "转换短网址：<br>";
echo '原网址：http://www.baidu.com<br>';
echo '短网址: ' . $shortUrl->hash . '<br>';
echo '<br>';

// 查询短网址
try {
    $shortUrl = ShortUrl::find($pdo, '2qpdgo3');

    echo "查询短网址：<br>";
    echo '短网址: 2qpdgo3<br>';
    echo '原网址：http://www.baidu.com<br>';
} catch (Exception $e) {
    die($e->getMessage());
}