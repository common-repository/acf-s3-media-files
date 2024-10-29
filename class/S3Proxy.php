<?php
declare(strict_types=1);

namespace Codemenschen\AcfS3;

use Aws\S3\S3Client;

/**
 * Class Proxy
 * @package Codemenschen\AcfS3
 */
class S3Proxy
{
    /**
     * @var S3Client
     */
    private $client;

    /**
     * @var string
     */
    private $bucket;

    /**
     * Proxy constructor.
     * @param S3Client $client
     * @param string $bucket
     */
    function __construct(S3Client $client, string $bucket)
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    /**
     * @param string $key
     * @param string $contentType
     * @return array
     */
    public function createMultipartUpload(string $key, string $contentType): array
    {
        $model = $this->client->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'ContentType' => $contentType,
            'ACL' => 'public-read',
        ]);
        return $model->toArray();
    }

    /**
     * @param string $key
     * @param string $uploadId
     * @return array
     */
    public function abortMultipartUpload(string $key, string $uploadId): array
    {
        $model = $this->client->abortMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);
        return $model->toArray();
    }

    /**
     * @param string $key
     * @param array $parts
     * @param string $uploadId
     * @return array
     */
    public function completeMultipartUpload(string $key, array $parts, string $uploadId): array
    {
        $model = $this->client->completeMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'MultipartUpload' => [
                'Parts' => $parts,
            ],
            'UploadId' => $uploadId,
        ]);
        return $model->toArray();
    }

    /**
     * @return array
     */
    public function listMultipartUploads(): array
    {
        $model = $this->client->listMultipartUploads([
            'Bucket' => $this->bucket,
        ]);
        return $model->toArray();
    }

    /**
     * @param string $key
     * @param int $partNumber
     * @param string $uploadId
     * @return array
     */
    public function signUploadPart(string $key, int $partNumber, string $uploadId): array
    {
        $command = $this->client->getCommand('uploadPart', [
            'Bucket' => $this->bucket,
            'Body' => '',
            'Key' => $key,
            'PartNumber' => $partNumber,
            'UploadId' => $uploadId,
        ]);
        $request = $this->client->createPresignedRequest($command, '+10 minutes');
        return [
            'Url' => (string)$request->getUri(),
        ];
    }

    /**
     * @param string $key
     * @return array
     */
    public function deleteObject(string $key): array
    {
        $model = $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);
        return $model->toArray();
    }

    /**
     * @param array $options
     * @return array
     */
    public function listObjects(array $options = []): array
    {
        return $this->client->listObjectsV2(array_merge($options, [
            'Bucket' => $this->bucket,
        ]))->toArray();
    }

    /**
     * @param string $key
     * @return string
     */
    public function getObjectUrl(string $key): string
    {
        return $this->client->getObjectUrl($this->bucket, $key);
    }
}
