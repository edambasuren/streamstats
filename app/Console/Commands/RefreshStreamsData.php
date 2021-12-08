<?php

namespace App\Console\Commands;

use App\Managers\TwitchManager;
use App\Models\Tag;
use App\Models\Stream;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshStreamsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:refresh-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes Streams Data';

    private $manager;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->manager = new TwitchManager();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $total = 1000;
        $first = 20;

        $after = null;

        $this->info('*** Starting Refresh of Streams Data');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Tag::truncate();
        Stream::truncate();
        DB::table('stream_tag')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $cnt = 0;
        while ($cnt < $total) {

            $resp = $this->manager->getStreams($first, $after);
            $streams = $resp['data'];
            $pagination = $resp['pagination'];
            $after = $pagination['cursor'];

            $tag_ids = [];
            foreach($streams as $stream) {
                $tag_ids = array_unique (array_merge ($tag_ids, $stream['tag_ids']));
            }

            $resp = $this->manager->getTags($tag_ids, 100, null);
            $tags = $resp['data'];
            $tag_data = [];
            foreach($tags as $tag) {
                $tag_data[ $tag['tag_id'] ] = [
                    'id' => $tag['tag_id'],
                    'name' => $tag['localization_names']['en-us'],
                    'descriptions' => $tag['localization_descriptions']['en-us'],
                ];
            }
            Tag::insertOrIgnore(array_values($tag_data));

            $stream_data = [];
            $pivot_data = [];
            foreach($streams as $stream) {
                $stream_data[] = [
                    'id' => $stream['id'],
                    'channel_name' => $stream['user_name'],
                    'stream_title' => $stream['title'],
                    'game_name' => $stream['game_name'],
                    'viewer_count' => $stream['viewer_count'],
                    'started_at' => new Carbon($stream['started_at']),
                ];
                foreach ($stream['tag_ids'] as $tag_id) {
                    if (array_key_exists($tag_id, $tag_data)) {
                        $pivot_data[] = [
                            'stream_id' => $stream['id'],
                            'tag_id' => $tag_id
                        ];
                    }
                }

            }
    
            Stream::insert($stream_data);

            DB::table('stream_tag')->insert($pivot_data);

            $cnt += count($stream_data);
            $this->info('- added ' . count($stream_data) . ' entries');
        }

        $this->info('*** Refresh of Streams Data Completed. Total=' . $cnt);
        return Command::SUCCESS;
    }
}
