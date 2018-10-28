@extends('larapress::layout')

@section('content')

    <h1>{{ $tag->name }}</h1>
    <p><a href="{{ url(config('larapress.path') . '/posts') }}">All posts</a></p>

    <h3>Posts</h3>
    <ul>
        @foreach ($tag->posts as $post)
            <li>
                <a href="{{ $post->path() }}">
                    {{ $post->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection