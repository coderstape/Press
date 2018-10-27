<?php

namespace vicgonvt\LaraPress\Console;

use Illuminate\Console\Command;
use vicgonvt\LaraPress\Actions\Database;
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
            $this->warn('Please publish the config file by running \'php artisan vendor:publish\'');

            return;
        }

        try {

            $posts = LaraPress::driver()->fetchPosts();

            if (LaraPress::database()->savePosts($posts)) {
                return $this->info('LaraPress process complete.');
            }

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
