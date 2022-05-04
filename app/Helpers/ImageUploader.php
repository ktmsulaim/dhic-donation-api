<?php

namespace App\Helpers;

use App\Models\Student;
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

        // create medium image
        $image = Image::make($file->getRealPath());
        $image->resize(512, 512, function ($constraint) {
            $constraint->aspectRatio();
        })->encode();

        Storage::disk('public')->put($path . '/photos/medium/'.$fileName, $image);

        return $fileName;
    }

    public static function delete($path, $fileName)
    {
        $thumbnail = $path . '/photos/thumbnail/' . $fileName;
        $medium = $path . '/photos/medium/' . $fileName;

        if (Storage::exists($thumbnail)) {
            Storage::delete($thumbnail);
        }

        if (Storage::exists($medium)) {
            Storage::delete($medium);
        }
    }

    public static function deleteAllUnused($path)
    {
        $files = Storage::files($path, true);

        foreach ($files as $file) {
            $fileName = basename($file);
            $thumbnail = $path . '/photos/thumbnail/' . $fileName;
            $medium = $path . '/photos/medium/' . $fileName;
            $isPhotoConnected = true;

            if($path == 'students') {
                $isPhotoConnected = Student::where('photo', $fileName)->first();
            }

            if (!$isPhotoConnected) {
                if (Storage::exists($thumbnail)) {
                    Storage::delete($thumbnail);
                }

                if (Storage::exists($medium)) {
                    Storage::delete($medium);
                }
            }
        }
    }
}