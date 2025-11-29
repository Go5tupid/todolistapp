<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title', 'is_completed', 'description', 'category_id', 'due_date', 'order'];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && !$this->is_completed;
    }

    public function isDueSoon()
    {
        return $this->due_date && $this->due_date->isFuture() && $this->due_date->diffInHours(now()) <= 24 && !$this->is_completed;
    }
}
