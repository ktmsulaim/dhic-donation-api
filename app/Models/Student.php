<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'photo', 'place', 'dob', 'adno', 'class', 'active'
    ];

    public function hasPhoto() {
        if($this->photo) {
            return Storage::exists('students/photos/thumbnail/' . $this->photo);
        }

        return false;
    }

    public function getPhotoUrl($size = 'thumbnail') {
        if($this->hasPhoto()) {
            return Storage::url('students/photos/' . $size . '/' . $this->photo);
        }
    }

    public function subscription() {
        return $this->hasOne(Subscription::class);
    }


    public function scopeActive($query)
    {
        $query->where('active', 1);
    }
}
