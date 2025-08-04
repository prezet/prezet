<?php

namespace Prezet\Prezet\Actions;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Prezet\Prezet\Exceptions\InvalidConfigurationException;
use Prezet\Prezet\Prezet;

class GetImage
{
    public function handle(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $this->validateFileExtension($extension);

        $size = $this->extractSize($path);
        $path = $this->removeSize($path);

        $image = $this->loadImage($path);

        if (isset($size)) {
            $image = $this->resizeImage($image, $size);
        }

        return $this->outputImage($image, $extension);
    }

    protected function validateFileExtension(string $extension): void
    {
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];

        if (! in_array($extension, $allowedExtensions)) {
            abort(404, 'Invalid file extension');
        }
    }

    protected function loadImage(string $path): GdImage
    {
        $imageStr = Storage::disk(Prezet::getPrezetDisk())->get('images/'.$path);
        if (! $imageStr) {
            abort(404);
        }

        $image = imagecreatefromstring($imageStr);
        if (! $image) {
            abort(500, 'Failed to create image from string');
        }

        return $image;
    }

    private function extractSize(string $path): ?int
    {
        $allowedWidths = config('prezet.image.widths');
        if (! is_array($allowedWidths)) {
            throw new InvalidConfigurationException('prezet.image.widths', $allowedWidths, 'is not an array');
        }

        $pattern = '/(.+)-(\d+)w\.(?:png|jpg|webp)$/';

        if (preg_match($pattern, $path, $matches) && in_array((int) $matches[2], $allowedWidths)) {
            return (int) $matches[2];
        }

        return null;
    }

    private function removeSize(string $path): string
    {
        $pattern = '/(.+)-(\d+)w\.(\w+)$/';

        $result = preg_replace($pattern, '$1.$3', $path);

        if (! $result) {
            abort(404, 'Invalid image path');
        }

        return $result;
    }

    private function resizeImage(GdImage $image, int $size): GdImage
    {
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        $ratio = $size / $originalWidth;
        $newHeight = (int) round($originalHeight * $ratio);
        $resizedImage = imagecreatetruecolor($size, $newHeight);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $size, $newHeight, $originalWidth, $originalHeight);
        imagedestroy($image);

        return $resizedImage;
    }

    private function outputImage(GdImage $image, string $extension): string
    {
        ob_start();
        switch ($extension) {
            case 'png':
                imagepng($image);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($image);
                break;
            case 'webp':
                imagewebp($image);
                break;
        }
        $output = ob_get_clean();
        imagedestroy($image);

        if (! $output) {
            abort(500, 'failed to output image');
        }

        return $output;
    }
}
