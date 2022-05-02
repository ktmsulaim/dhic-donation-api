<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Image;

class ImageUploader
{
    public static function upload(UploadedFile $file, $path)
    {
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        
        // create thumbnail
        $image = Image::make($file->getRealPath());
        $image->resize(150, 150, function ($constraint) {
            $constraint->aspectRatio();
        })->encode();

        Storage::disk('public')->put($path . '/photos/thumbnail/'.$fileName, $image);

        // create large image
        $image = Image::make($file->getRealPath());
        $image->resize(512, 512, function ($constraint) {
            $constraint->aspectRatio();
        })->encode();

        Storage::disk('public')->put($path . '/photos/medium/'.$fileName, $image);

        return $fileName;
    }

    public static function delete($path, $fileName)
    {
        $thumbnail = storage_path($path . '/photos/thumbnail/' . $fileName);
        $large = storage_path($path . '/photos/medium/' . $fileName);

        if (Storage::exists($thumbnail)) {
            Storage::delete($thumbnail);
        }

        if (Storage::exists($large)) {
            Storage::delete($large);
        }
    }
}