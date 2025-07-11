@extends('press::layout')

@section('content')

    <h1>SERIES: {{ $series->title }}</h1>
    <p><a href="{{ url(config('press.path') . '/posts') }}">All posts</a></p>

    <h3>Posts</h3>
    <ul>
        @foreach ($series->posts as $post)
            <li>
                <a href="{{ $post->path() }}">
                    {{ $post->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection