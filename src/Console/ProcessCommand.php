<?php

namespace vicgonvt\LaraPress\Console;

use Illuminate\Console\Command;
use vicgonvt\LaraPress\Actions\Database;

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
        $driver = 'vicgonvt\LaraPress\Drivers\\' . title_case(config('larapress.driver')) . 'Driver';

        try {

            $posts = (new $driver)->fetchPosts();

            if ((new Database())->savePosts($posts)) {
                return $this->info('LaraPress Updated Successfully');
            }

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
