<?php

namespace vicgonvt\LaraPress\Console;

use Illuminate\Console\Command;
use vicgonvt\LaraPress\LaraPress;

class ProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larapress:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all blog posts.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (LaraPress::configNotPublished()) {
            return $this->warn('Please publish the config file by running \'php artisan vendor:publish\'');
        }

        try {

            $posts = LaraPress::driver()->fetchPosts();

            if ($posts && LaraPress::database()->savePosts($posts)) {
                return $this->info('LaraPress process complete.');
            }

            $this->warn('No posts were updated.');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
