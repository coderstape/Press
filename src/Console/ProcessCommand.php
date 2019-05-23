<?php

namespace coderstape\Press\Console;

use Illuminate\Console\Command;
use coderstape\Press\Press;

class ProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'press:process';

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
        if (Press::configNotPublished()) {
            return $this->warn('Please publish the config file by running' .
                ' \'php artisan vendor:publish --tag=press-config\'');
        }

        try {

            $posts = Press::driver()->fetchPosts();

            if ($posts && Press::database()->savePosts($posts)) {
                return $this->info('Press process complete.');
            }

            $this->warn('No posts were updated.');

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
