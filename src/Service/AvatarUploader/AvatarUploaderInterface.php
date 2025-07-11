<?php
declare(strict_types=1);

namespace App\Service\AvatarUploader;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use InvalidArgumentException;
use RuntimeException;

interface AvatarUploaderInterface
{
    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function upload(?UploadedFile $file): ?string;

    /**
     * @throws RuntimeException
     */
    public function delete(?string $avatarPath): void;
}
