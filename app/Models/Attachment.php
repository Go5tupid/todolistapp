<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'task_id',
        'filename',
        'original_filename',
        'path',
        'size',
        'mime_type'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function getSizeForHumansAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
