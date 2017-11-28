<?php

namespace App\Models;

class Topic extends Model
{
    protected $fillable = ['title', 'body', 'category_id', 'excerpt', 'slug'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public  function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithOrder($query,$order)
    {
    	switch ($order) {
    		case 'recent':
    			$query = $this->recent();
    			break;
    		
    		default:
    			$query = $this->recentReplied();
    			break;
    	}

    	return $query->with('user','category');
    }

    public function scopeRecentReplied($query)
    {
    	return $query->orderBy('updated_at','desc');
    }

    public function scopeRecent($query)
    {
    	return $query->orderBy('created_at','desc');
    }
}
