<nav class="flex items-center justify-between flex-wrap bg-teal p-6 mb-6">
    <div class="container mx-auto flex">
        <a href="{{ url(config('press.path')) }}">
            <div class="flex items-center flex-no-shrink text-white mr-6">
                <span class="font-semibold text-xl tracking-tight">Press</span>
            </div>
        </a>

        <div class="w-full block flex-grow lg:flex lg:items-center lg:w-auto">
            <div class="text-sm lg:flex-grow">
                <a href="{{ url(config('press.path') . '/posts') }}"
                   class="block mt-4 lg:inline-block lg:mt-0 text-teal-lighter hover:text-white mr-4">
                    Posts
                </a>
                <a href="{{ url(config('press.path') . '/tags') }}"
                   class="block mt-4 lg:inline-block lg:mt-0 text-teal-lighter hover:text-white mr-4">
                    Tags
                </a>
                <a href="{{ url(config('press.path') . '/series') }}"
                   class="block mt-4 lg:inline-block lg:mt-0 text-teal-lighter hover:text-white">
                    Series
                </a>
            </div>
        </div>
    </div>
</nav>