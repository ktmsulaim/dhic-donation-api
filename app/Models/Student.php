<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'photo', 'place', 'dob', 'adno', 'class',
    ];

    public function hasPhoto() {
        if($this->photo) {
            return Storage::disk('public')->exists('students/photos/thumbnail/' . $this->photo);
        }

        return false;
    }

    public function getPhotoUrl($size = 'thumbnail') {
        if($this->hasPhoto()) {
            return Storage::disk('public')->url('students/photos/' . $size . '/' . $this->photo);
        }
    }
}
