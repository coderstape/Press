<nav class="flex items-center justify-between flex-wrap bg-teal p-6 mb-6">
    <div class="container mx-auto flex">
        {{-- Press::path(), never config('press.path') directly: the
     accessor carries the /blog default. A bare config read is
     null on unpublished-config sites, and url(null) returns the
     UrlGenerator OBJECT -- fatal when Blade escapes it. --}}
        <a href="{{ url(Press::path()) }}">
            <div class="flex items-center flex-no-shrink text-white mr-6">
                <span class="font-semibold text-xl tracking-tight">Press</span>
            </div>
        </a>

        <div class="w-full block flex-grow lg:flex lg:items-center lg:w-auto">
            <div class="text-sm lg:flex-grow">
                <a href="{{ url(Press::path() . '/posts') }}"
                   class="block mt-4 lg:inline-block lg:mt-0 text-teal-lighter hover:text-white mr-4">
                    Posts
                </a>
                <a href="{{ url(Press::path() . '/tags') }}"
                   class="block mt-4 lg:inline-block lg:mt-0 text-teal-lighter hover:text-white mr-4">
                    Tags
                </a>
                <a href="{{ url(Press::path() . '/series') }}"
                   class="block mt-4 lg:inline-block lg:mt-0 text-teal-lighter hover:text-white">
                    Series
                </a>
            </div>
        </div>
    </div>
</nav>