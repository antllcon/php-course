<?php
declare(strict_types=1);

namespace App\Service\AvatarUploader;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use InvalidArgumentException;
use RuntimeException;
use Exception;

class AvatarUploader implements AvatarUploaderInterface
{
    private const UPLOAD_DIR = __DIR__ . '/../../../public/uploads/';
    private const MAX_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    public function upload(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }

        if (!$file->isValid()) {
            throw new RuntimeException('An error occurred during file upload: ' . $file->getErrorMessage());
        }

        if ($file->getSize() > self::MAX_SIZE) {
            throw new InvalidArgumentException('File is too large. Maximum size is ' . (self::MAX_SIZE / (1024 * 1024)) . 'MB');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Invalid file type. Only JPG, PNG, and GIF are allowed');
        }

        $filename = uniqid('avatar_', true) . '.' . $file->guessExtension();

        try {
            $file->move(self::UPLOAD_DIR, $filename);

        } catch (Exception $e) {
            throw new RuntimeException('Failed to save the uploaded file: ' . $e->getMessage());
        }

        return '/uploads/' . $filename;
    }

    public function delete(?string $avatarPath): void
    {
        if (!$avatarPath) {
            return;
        }

        $fullPath = self::UPLOAD_DIR . basename($avatarPath);

        if (file_exists($fullPath) && is_file($fullPath)) {
            try {
                unlink($fullPath);

            } catch (Exception $e) {
                throw new RuntimeException('Failed to delete avatar file: ' . $e->getMessage());
            }
        }
    }
}
