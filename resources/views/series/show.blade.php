@extends('press::layout')

@section('content')

    {{-- Restored from the swap (see series/index). Two deliberate
         deltas from the stranded original: Press::path() instead of
         a raw config read (see nav), and activePosts instead of
         posts so draft titles don't leak onto public series pages
         (judgment call, veto ok -- matches authors/show). --}}
    <h1>SERIES: {{ $series->title }}</h1>
    <p><a href="{{ url(Press::path() . '/posts') }}">All posts</a></p>

    <h3>Posts</h3>
    <ul>
        @foreach ($series->activePosts as $post)
            <li>
                <a href="{{ $post->path() }}">
                    {{ $post->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection
