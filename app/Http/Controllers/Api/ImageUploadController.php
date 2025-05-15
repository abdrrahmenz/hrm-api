<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Traits\HttpResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class ImageUploadController extends Controller
{
    use HttpResponse;

    public function __construct()
    {
        // No authentication middleware for this controller
        // All methods in this controller are public

        // Check if the images table exists, if not run the migration
        $this->checkAndCreateImagesTable();
    }

    /**
     * Check if images table exists and create it if not
     */
    private function checkAndCreateImagesTable()
    {
        if (!\Schema::hasTable('images')) {
            \Schema::create('images', function ($table) {
                $table->id();
                $table->string('filename');
                $table->string('file_path');
                $table->string('url');
                $table->string('extension');
                $table->string('mime_type')->nullable();
                $table->integer('size')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Handle the image upload request
     */
    public function index()
    {
        $request = Request::capture();

        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $extension = $request->file('image')->extension();
                $randomName = md5(uniqid()) . time();
                $filename = $randomName . '.' . $extension;

                if (in_array($extension, ['png', 'jpg', 'jpeg'])) {
                    // Get the uploaded file
                    $file = $request->file('image');

                    // Ensure directory exists
                    $directory = public_path('storage/images');
                    if (!file_exists($directory)) {
                        mkdir($directory, 0777, true);
                    }

                    // Save file using file_put_contents instead of move
                    $contents = file_get_contents($file->getRealPath());
                    file_put_contents($directory . '/' . $filename, $contents);
                    $path = $directory . '/' . $filename;
                    $imageUrl = Config::get('app.url') . '/storage/images/' . $filename;

                    // Save to database
                    $image = new Image();
                    $image->filename = $filename;
                    $image->file_path = $path;
                    $image->url = $imageUrl;
                    $image->extension = $extension;
                    $image->mime_type = $request->file('image')->getMimeType();
                    $image->size = $request->file('image')->getSize();
                    $image->description = $request->description ?? null;
                    $image->save();

                    return $this->success([
                        'image_id' => $image->id,
                        'image_url' => $imageUrl,
                        'image_extension' => $extension,
                    ], 'image uploaded successfully');
                } else {
                    return $this->error('', 'This ' . $extension . ' file not supported');
                }
            } elseif (! $request->hasFile('image')) {
                return $this->error('', 'No file found to upload');
            }
        } catch (\Throwable $e) {
            return $this->httpError('Error uploading image: ' . $e->getMessage());
        }
    }

    /**
     * Get all uploaded images
     */
    public function getImages()
    {
        try {
            $images = Image::latest()->get();

            // For each image, update the URL and verify file existence
            foreach ($images as $image) {
                // Get actual filename from path
                $filename = $image->filename;

                // Check if file exists in new location, if not try to move it
                $newPath = public_path('storage/images/' . $filename);
                $oldPath = storage_path('app/public/images/' . $filename);

                if (!file_exists($newPath) && file_exists($oldPath)) {
                    copy($oldPath, $newPath);
                }

                // Update the URL to ensure it uses the correct APP_URL
                $image->url = Config::get('app.url') . '/storage/images/' . $filename;
                $image->save();
            }

            return $this->success($images, 'Images fetched successfully');
        } catch (\Throwable $e) {
            return $this->httpError($e->getMessage());
        }
    }
}
