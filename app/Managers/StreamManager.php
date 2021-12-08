<?php

namespace App\Managers;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class StreamManager
{

    public function streamsPerGame()
    {
        $result = DB::table('streams')
             ->select(DB::raw('game_name,  COUNT(*) as count'))
             ->groupBy('game_name')
             ->orderBy('count', 'desc')
             ->get();
        return $result;
    }

    public function topGamesPerGame()
    {
        $result = DB::table('streams')
             ->select(DB::raw('game_name,  SUM(viewer_count) as count'))
             ->groupBy('game_name')
             ->orderBy('count', 'desc')
             ->get();
        return $result;
    }

    public function medianNumberOfViewers()
    {
        $result = DB::table('streams')
             ->select(DB::raw('viewer_count'))
             ->orderBy('viewer_count')
             ->get();
        $size = $result->count();
        $middle = floor($size/2);

        return $result[$middle]->viewer_count;
    }

    public function top100Streams()
    {
        $result = DB::table('streams')
             ->select(DB::raw('stream_title,  SUM(viewer_count) as count'))
             ->groupBy('stream_title')
             ->orderBy('count', 'desc')
             ->limit(100)
             ->get();
        return $result;
    }
    
    public function numberOfStreamsByHour()
    {
        $result = DB::table('streams')
             ->select(DB::raw('SUBSTR(started_at,1,13) as hour,  COUNT(1) as count'))
             ->groupBy(DB::raw("SUBSTR(started_at,1,13)"))
             ->orderBy('hour')
             ->get();
        return $result;
    }
}
