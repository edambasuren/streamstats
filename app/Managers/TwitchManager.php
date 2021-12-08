<?php

namespace App\Managers;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TwitchManager
{

    private $helix_url;
    private $client_id;
    private $client_secret;
    private $oauth2_url;

    /**
     * Create Twitch Manager.
     *
     * @return void
     */
    public function __construct()
    {
        $this->helix_url = config('twitch.helix_url');
        $this->client_id = config('twitch.client_id');
        $this->client_secret = config('twitch.client_secret');
        $this->oauth2_url = config('twitch.oauth2_url');
        
    }

    /**
     * Get top streams.
     *
     * @return void
     */
    public function getStreams($first=20, $after=null)
    {
        $access_token = Cache::get('TwitchBearerToken');
        if (!$access_token) {
            $this->refreshToken();
            $access_token = Cache::get('TwitchBearerToken');
        }

        $param = [
            'first' => $first
        ];
        if ($after) {
            $param['after'] = $after;
        }
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Client-Id' => $this->client_id
        ])->get($this->helix_url . '/streams', $param);

        $response->throw();

        return $response->json();
    }

    //GET https://api.twitch.tv/helix/tags/streams
    //GET https://api.twitch.tv/helix/streams/tags

    /**
     * Get tags.
     *
     * @return void
     */
    public function getTags($tag_ids, $first=20, $after=null)
    {
        $access_token = Cache::get('TwitchBearerToken');
        if (!$access_token) {
            $this->refreshToken();
            $access_token = Cache::get('TwitchBearerToken');
        }

        $url = $this->helix_url . '/tags/streams?first=' . $first;
        if ($after) {
            $url .= '&after=' .  $after;
        }
        foreach ($tag_ids as $tag_id) {
            $url .= '&tag_id=' .  $tag_id;
        }
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Client-Id' => $this->client_id
        ])->get($url);

        $response->throw();

        return $response->json();
    }

    /**
     * Refresh token
     *
     * @return void
     */
    protected function refreshToken()
    {
        $response = Http::post($this->oauth2_url . '/token', [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials',
        ]);

        $response->throw();

        $resp = $response->json();
        $access_token = $resp['access_token'];
        $expires_in = $resp['expires_in']; //default 60 days

        Cache::put('TwitchBearerToken', $access_token, $expires_in);

    }
}
