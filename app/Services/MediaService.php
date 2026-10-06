<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaService
{
    /**
     * Store an uploaded file and create a Media record.
     */
    public function upload(
        UploadedFile $file,
        string $directory,
        string $visibility = 'private',
        ?User $uploader = null,
        ?string $disk = null
    ): Media {
        $disk = $disk ?? ($visibility === 'public' ? 'public' : 'private');
        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $filename = "{$uuid}." . ($extension ?: 'bin');

        $storedPath = $file->storeAs($directory, $filename, $disk);

        return Media::create([
            'uuid' => $uuid,
            'disk' => $disk,
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'visibility' => $visibility,
            'uploaded_by' => $uploader?->id,
        ]);
    }

    /**
     * Get a streamed download response for a private or protected media item.
     */
    public function download(Media $media): StreamedResponse
    {
        return Storage::disk($media->disk)->download($media->path, $media->original_name);
    }

    /**
     * Stream a media item inline (e.g. for browser document viewing).
     */
    public function response(Media $media): \Symfony\Component\HttpFoundation\Response
    {
        return Storage::disk($media->disk)->response($media->path, $media->original_name);
    }

    /**
     * Delete the file from disk and remove the Media record.
     */
    public function delete(Media $media): bool
    {
        Storage::disk($media->disk)->delete($media->path);
        return $media->delete();
    }
}
