<?php
/**
 * Created by PhpStorm.
 * User: wxp
 * Date: 2017/12/8
 * Time: 22:49
 */

namespace  App\Observers;

use App\Models\Link;
use Cache;

class LinkObserver
{
    public function saved(Link $link)
    {
        Cache::forget($link->cache_key);
    }
}