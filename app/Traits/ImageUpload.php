<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

trait ImageUpload
{
    // Normal upload method
    public function upload($file, $directory)
    {
        $path = public_path('uploads/' . $directory);
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
    
        $uuid = Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $fileName = $uuid . '.' . $extension;

        $file->move($path, $fileName);
  
        return 'uploads/' . $directory . '/' . $fileName;
    }

    // Method to remove a file
    public function remove($filePath)
    {
        if (File::exists(public_path($filePath)) && !is_dir(public_path($filePath))) {
            return unlink(public_path($filePath));
        }
        return false;
    }
}
