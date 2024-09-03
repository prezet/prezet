<?php

namespace BenBjurstrom\Prezet\Http\Controllers;

use BenBjurstrom\Prezet\Actions\GetImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImageController
{
    public function __invoke(Request $request, string $path): Response
    {
        $file = GetImage::handle($path);

        return response($file, 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
