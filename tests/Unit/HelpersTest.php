<?php

namespace coderstape\Press\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;

class HelpersTest extends TestCase
{
    #[Test]
    public function theme_prefixes_the_package_namespace_by_default()
    {
        // LOOKS WRONG BUT ISN'T (quite): the helper joins with '.',
        // producing 'press::.posts.index' -- namespace 'press', view
        // '.posts.index'. The leading dot becomes a leading slash in
        // the compiled path ('//posts/index.blade.php'), which the
        // filesystem tolerates, so it has always resolved. Do NOT
        // change the join without auditing custom-theme configs,
        // where the '.' is load-bearing ('mytheme' . '.' . $view).
        $view = theme('posts.index');

        $this->assertEquals('press::.posts.index', $view->name());
    }

    #[Test]
    public function theme_uses_a_configured_custom_theme_as_a_view_prefix()
    {
        $base = sys_get_temp_dir() . '/press-theme-test';
        File::ensureDirectoryExists($base . '/custom/posts');
        File::put($base . '/custom/posts/index.blade.php', 'custom theme');
        View::addLocation($base);

        config(['press.theme' => 'custom']);

        $view = theme('posts.index');

        $this->assertEquals('custom.posts.index', $view->name());
        $this->assertEquals('custom theme', $view->render());
    }
}
