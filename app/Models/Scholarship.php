<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scholarship extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'nama_beasiswa',
        'slug',
        'benua',
        'negara',
        'jenjang',
        'deskripsi',
        'deadline',
        'kategori',
        'funding_type',
        'jurusan',
        'benefit',
        'persyaratan',
        'sumber',
        'url',
        'url_asli',
        'embedding'
    ];

    public function bookmarkedBy()
    {
        return $this->hasMany(Bookmark::class);
    }
}
