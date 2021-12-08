<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Managers\StreamManager;
use App\Models\Tag;
use App\Models\Stream;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $client_id = config('twitch.client_id');
        $app_url = config('app.url');

        $stream_manager = new StreamManager();
        $streams_per_game = $stream_manager->streamsPerGame();
        $top_games_per_game = $stream_manager->topGamesPerGame();
        $median_number_of_viewers = $stream_manager->medianNumberOfViewers();
        $top_100_streams = $stream_manager->top100Streams();
        $number_of_streams_by_hour = $stream_manager->numberOfStreamsByHour();

        return view('streamstats', compact('client_id', 'app_url', 'streams_per_game', 'top_games_per_game', 
            'median_number_of_viewers', 'top_100_streams', 'number_of_streams_by_hour'));
    }

    public function followed(Request $request)
    {
        $user_name = $request['user_name'];
        $title = $request['title'];

        $stream = Stream::where('stream_title', $title)->first();
        //print_r($stream);
        if ($stream) {
            return true;    
        } else {
            return false;
        }
    }

    public function tag(Request $request)
    {
        $tag_id = $request['tag_id'];

        $tag = Tag::where('id', $tag_id)->first();
        if ($tag) {
            return $tag->name;    
        } else {
            return false;
        }
    }
    
}
