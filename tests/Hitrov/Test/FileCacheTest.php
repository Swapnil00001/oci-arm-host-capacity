<?php

namespace Hitrov\Test;

use Hitrov\FileCache;
use Hitrov\Test\Traits\DefaultConfig;
use Hitrov\Test\Traits\LoadEnv;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    const ENV_FILENAME = '.env.test';

    use DefaultConfig, LoadEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadEnv();

        // Create test private key if it doesn't exist
        $keyPath = '/tmp/test_key.pem';
        if (!file_exists($keyPath)) {
            $privateKey = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            openssl_pkey_export_to_file($privateKey, $keyPath);
            chmod($keyPath, 0600);
        }

        if (file_exists($this->getCacheFilename())) {
            unlink($this->getCacheFilename());
        }
    }

    public function testGetCacheKey(): void
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $expectedKey = md5(json_encode($config));

        $this->assertEquals(
            $expectedKey,
            $cache->getCacheKey('foo'),
        );
    }

    public function testCacheFileCreated(): void
    {
        $config = $this->getDefaultConfig();
        $api = $this->getDefaultApi();

        $api->setCache(new FileCache($config));

        $this->assertTrue(
            file_exists(sprintf('%s/%s', getcwd(), 'oci_cache.json')),
        );
    }

    public function testAddsCacheFileContents()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $cache->add([1, 'one'], 'foo');

        $expectedKey = md5(json_encode($config));
        $expected = [
            "foo" => [
                $expectedKey => [
                    1,
                    "one"
                ]
            ]
        ];

        $this->assertEquals(
            $expected,
            json_decode(file_get_contents($this->getCacheFilename()), true),
        );
    }

    public function testUpdatesCacheFileContents()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $expectedKey = md5(json_encode($config));
        $existingCache = json_encode([
            "foo" => [
                $expectedKey => [
                    1,
                    "one"
                ]
            ]
        ], JSON_PRETTY_PRINT);

        file_put_contents($this->getCacheFilename(), $existingCache);

        $cache->add([2, 'two'], 'bar');

        $expected = [
            "foo" => [
                $expectedKey => [
                    1,
                    "one"
                ]
            ],
            "bar" => [
                $expectedKey => [
                    2,
                    "two"
                ]
            ]
        ];

        $this->assertEquals(
            $expected,
            json_decode(file_get_contents($this->getCacheFilename()), true),
        );
    }

    public function testUpdatesWithDifferentConfig()
    {
        $config = $this->getDefaultConfig();
        $firstConfigKey = md5(json_encode($config));
        
        $config->bootVolumeId = 'baz';
        $secondConfigKey = md5(json_encode($config));
        
        $cache = new FileCache($config);

        $existingCache = json_encode([
            "foo" => [
                $firstConfigKey => [
                    1,
                    "one"
                ]
            ]
        ], JSON_PRETTY_PRINT);

        file_put_contents($this->getCacheFilename(), $existingCache);

        $cache->add([11, 'eleven'], 'foo');

        $expected = [
            "foo" => [
                $firstConfigKey => [
                    1,
                    "one"
                ],
                $secondConfigKey => [
                    11,
                    "eleven"
                ]
            ]
        ];

        $this->assertEquals(
            $expected,
            json_decode(file_get_contents($this->getCacheFilename()), true),
        );
    }

    public function testGet()
    {
        $config = $this->getDefaultConfig();
        $cache = new FileCache($config);

        $cache->add([1, 'one'], 'foo');

        $this->assertEquals(
            [1, 'one'],
            $cache->get('foo'),
        );
    }

    private function getCacheFilename(): string
    {
        return sprintf('%s/%s', getcwd(), 'oci_cache.json');
    }
}