<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploader
{
    private Cloudinary $cloudinary;

    public function __construct(string $cloudName, string $apiKey, string $apiSecret)
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => [
                'secure' => true, // Use HTTPS
            ],
        ]);
    }

    public function uploadImage(UploadedFile $file): ?string
    {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        try {
            $uploadResult = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder' => 'symfony_forms_project', // Optional: organize uploads in a specific folder
            ]);

            return $uploadResult['secure_url'] ?? null;
        } catch (\Exception $e) {
            // Log the error for debugging (e.g., using Monolog)
            // $this->logger->error('Cloudinary upload failed: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteImage(string $imageUrl): bool
    {
        try {
            $publicId = $this->extractPublicIdFromUrl($imageUrl);
            if (!$publicId) {
                return false;
            }

            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return ($result['result'] ?? '') === 'ok';
        } catch (\Exception $e) {
            // Log the error
            return false;
        }
    }

    private function extractPublicIdFromUrl(string $imageUrl): ?string
    {
        // Example: https://res.cloudinary.com/your_cloud_name/image/upload/v12345/folder/public_id.jpg
        // Needs to extract 'folder/public_id'
        $path = parse_url($imageUrl, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        // Remove leading /image/upload/ and version string (e.g., /v12345)
        $parts = explode('/', $path);
        $publicIdParts = [];
        $foundUpload = false;
        foreach ($parts as $part) {
            if ($part === 'upload') {
                $foundUpload = true;
                continue;
            }
            if ($foundUpload && !preg_match('/^v\d+$/', $part)) { // Skip version numbers
                $publicIdParts[] = $part;
            }
        }

        $publicId = implode('/', $publicIdParts);
        $publicId = pathinfo($publicId, PATHINFO_DETERMINED_FILENAME); // Remove extension

        return $publicId === '' ? null : $publicId;
    }
}