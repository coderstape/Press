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
        if ($this->configNotPublished()) {
            $this->warn('Please publish the config file by running \'php artisan vendor:publish\'');

            return;
        }

        try {

            $driver = $this->getDriverClassName();
            
            $posts = (new $driver)->fetchPosts();

            if ((new Database())->savePosts($posts)) {
                return $this->info('LaraPress process complete.');
            }

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Get the driver.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function getDriver()
    {
        return config('larapress.driver');
    }

    /**
     * Parse the driver class name.
     *
     * @return string
     */
    protected function getDriverClassName()
    {
        return 'vicgonvt\LaraPress\Drivers\\' . title_case($this->getDriver()) . 'Driver';
    }

    /**
     * Check if config file has been set.
     *
     * @return bool
     */
    protected function configNotPublished()
    {
        return is_null($this->getDriver());
    }
}
