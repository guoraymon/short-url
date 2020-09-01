<?php

namespace App\Entities;

use Exception;
use lastguest\Murmur;
use PDO;
use PDOException;

class ShortUrl
{
    public $pdo;
    public $hash;
    public $salt;
    public $url;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param PDO $pdo
     * @param string $url
     * @return ShortUrl
     */
    static public function new(PDO $pdo, string $url)
    {
        $hash = Murmur::hash3($url);

        $shortUrl = self::parse($pdo, [
            'url' => $url,
            'hash' => $hash
        ]);

        return $shortUrl->store();
    }

    /**
     * 查找短网址
     * @param PDO $pdo
     * @param String $hash
     * @return ShortUrl
     * @throws Exception
     */
    static public function find(PDO $pdo, string $hash): self
    {
        $stmt = $pdo->prepare("SELECT hash, salt, url FROM short_url WHERE hash=:hash");
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();

        $res = $stmt->fetch();
        if ($res) {
            return self::parse($pdo, $res);
        } else {
            throw new Exception('not found!');
        }
    }

    /**
     * 根据Url查找短网址
     * @param PDO $pdo
     * @param string $url
     * @return ShortUrl
     * @throws Exception
     */
    static public function findByUrl(PDO $pdo, string $url): self
    {
        $stmt = $pdo->prepare("SELECT hash, salt, url FROM short_url WHERE url=:url");
        $stmt->bindParam(':url', $url);
        $stmt->execute();

        $res = $stmt->fetch();
        if ($res) {
            return self::parse($pdo, $res);
        } else {
            throw new Exception('not found!');
        }
    }

    /**
     * @param $pdo
     * @param $array
     * @return ShortUrl
     */
    static private function parse($pdo, $array)
    {
        $shortUrl = new self($pdo);
        $shortUrl->url = $array['url'];
        $shortUrl->hash = $array['hash'];
        $shortUrl->salt = $array['salt'];
        return $shortUrl;
    }

    /**
     * throw
     * @return ShortUrl
     */
    private function store(): self
    {
        try {
            // 预处理 SQL 并绑定参数
            $stmt = $this->pdo->prepare("INSERT INTO short_url (hash, salt, url) VALUES (:hash, :salt, :url)");
            $stmt->bindParam(':hash', $this->hash);
            $stmt->bindParam(':salt', $this->salt);
            $stmt->bindParam(':url', $this->url);
            $stmt->execute();
            return $this;
        } catch (PDOException $e) {
            switch ($e->getCode()) {
                // 记录已存在
                case '23000':
                    // 取出已存在的短网址
                    try {
                        return ShortUrl::findByUrl($this->pdo, $this->url);
                    } catch (Exception $e) {
                        $this->salt = rand(1000, 9999);
                        $this->hash = Murmur::hash3("{$this->url}?{$this->salt}");
                        return $this->store();
                    }
                // 未知错误
                default:
                    die($e->getMessage());
            }
        }
    }
}