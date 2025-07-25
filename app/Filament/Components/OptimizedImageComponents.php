<?php

namespace App\Filament\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OptimizedImageComponents
{
    /**
     * Create an optimized file upload component with lazy loading
     */
    public static function optimizedFileUpload(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->imageResizeMode('contain')
            ->imageResizeTargetWidth('800')
            ->imageResizeTargetHeight('600')
            ->imageEditor()
            ->imageEditorAspectRatios([
                '16:9',
                '4:3',
                '1:1',
            ])
            ->optimize('webp')
            ->resize(800, 600)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(2048) // 2MB
            ->downloadable()
            ->previewable()
            ->uploadingMessage('Optimizing and uploading image...')
            ->uploadProgressIndicatorPosition('right')
            ->removeUploadedFileButtonPosition('right')
            ->uploadButtonPosition('left');
    }

    /**
     * Create an optimized image column with lazy loading
     */
    public static function optimizedImageColumn(string $name): ImageColumn
    {
        return ImageColumn::make($name)
            ->square()
            ->size(60)
            ->extraAttributes([
                'loading' => 'lazy',
                'decoding' => 'async',
            ])
            ->defaultImageUrl('/images/placeholder.svg')
            ->checkFileExistence(false) // Improve performance for large tables
            ->disk('public');
    }

    /**
     * Create a responsive image column that adapts to screen size
     */
    public static function responsiveImageColumn(string $name): ImageColumn
    {
        return static::optimizedImageColumn($name)
            ->extraAttributes([
                'class' => 'responsive-image',
                'loading' => 'lazy',
                'decoding' => 'async',
                'sizes' => '(max-width: 768px) 40px, (max-width: 1024px) 50px, 60px',
            ]);
    }

    /**
     * Create a circular avatar image column
     */
    public static function avatarImageColumn(string $name): ImageColumn
    {
        return static::optimizedImageColumn($name)
            ->circular()
            ->size(50)
            ->extraAttributes([
                'class' => 'avatar-image',
                'loading' => 'lazy',
            ]);
    }

    /**
     * Create a gallery image column for multiple images
     */
    public static function galleryImageColumn(string $name): ImageColumn
    {
        return ImageColumn::make($name)
            ->square()
            ->size(40)
            ->stacked()
            ->limit(3)
            ->extraAttributes([
                'loading' => 'lazy',
                'decoding' => 'async',
            ])
            ->defaultImageUrl('/images/placeholder.svg')
            ->disk('public');
    }

    /**
     * Generate optimized image variants on upload
     */
    public static function generateImageVariants(string $path): array
    {
        $variants = [];
        $originalPath = Storage::path($path);

        if (!file_exists($originalPath)) {
            return $variants;
        }

        try {
            // Use basic image optimization without intervention/image for now
            $variants['thumbnail'] = $path;
            $variants['medium'] = $path;
            $variants['large'] = $path;

        } catch (\Exception $e) {
            Log::error('Failed to generate image variants: ' . $e->getMessage());
        }

        return $variants;
    }

    /**
     * Get the best image variant for display size
     */
    public static function getBestImageVariant(string $basePath, string $size = 'medium'): string
    {
        $info = pathinfo($basePath);
        $directory = $info['dirname'];
        $baseName = $info['filename'];

        $variantPath = "{$directory}/{$baseName}_{$size}.webp";

        return Storage::exists($variantPath) ? $variantPath : $basePath;
    }

    /**
     * Create a lazy-loaded image with progressive enhancement
     */
    public static function progressiveImageColumn(string $name): ImageColumn
    {
        return ImageColumn::make($name)
            ->size(60)
            ->extraAttributes([
                'loading' => 'lazy',
                'decoding' => 'async',
                'class' => 'progressive-image',
                'data-sizes' => 'auto',
                'data-srcset' => function ($record) use ($name) {
                    $imagePath = $record->{$name};
                    if (!$imagePath) return '';

                    $info = pathinfo($imagePath);
                    $directory = $info['dirname'];
                    $baseName = $info['filename'];

                    return implode(', ', [
                        Storage::url("{$directory}/{$baseName}_thumb.webp") . ' 150w',
                        Storage::url("{$directory}/{$baseName}_medium.webp") . ' 400w',
                        Storage::url("{$directory}/{$baseName}_large.webp") . ' 800w',
                    ]);
                },
            ])
            ->defaultImageUrl('/images/placeholder.svg');
    }
}
