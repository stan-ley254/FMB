<?php

namespace App\Services;

use App\Models\OrderItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class OrderArtworkService
{
    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    public function attachToOrderItem(OrderItem $orderItem, array $files): array
    {
        $storedPaths = [];

        try {
            foreach ($files as $file) {
                $mimeType = $file->getMimeType();
                $path = $file->store('artworks', 'local');

                if ($path === false) {
                    throw new RuntimeException('Unable to store the uploaded artwork.');
                }

                $storedPaths[] = $path;
                $isPdf = $mimeType === 'application/pdf';

                $orderItem->artworks()->create([
                    'purpose' => $isPdf ? 'design' : 'proof',
                    'file_path' => $path,
                    'file_type' => $isPdf ? 'pdf' : 'image',
                ]);
            }
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $exception;
        }

        return $storedPaths;
    }
}
