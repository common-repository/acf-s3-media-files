<?php
declare(strict_types=1);

namespace Codemenschen\AcfS3;

class S3Item
{
    /**
     * @var string
     */
    private $bucket;

    /**
     * @var string
     */
    private $key;

    /**
     * acf_s3_item constructor.
     * @param string $bucket
     * @param string $key
     */
    function __construct(string $bucket, string $key)
    {
        $this->bucket = $bucket;
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return sprintf('https://%s.s3.amazonaws.com/%s', $this->bucket, $this->key);
    }

    /**
     * @return string
     */
    public function getBasename(): string
    {
        return basename($this->key);
    }
}
