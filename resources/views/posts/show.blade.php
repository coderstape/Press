@extends('larapress::layout')

@section('content')

    <h1>{{ $post->title }}</h1>
    <h2><a href="#">{{ $post->series->title }}</a></h2>
    <p><a href="{{ url(config('larapress.path') . '/posts') }}">All posts</a></p>

    <h3>Tags</h3>
    <ul>
        @foreach ($post->tags as $tag)
            <li>
                <a href="{{ $tag->path() }}">
                    {{ $tag->name }}
                </a>
            </li>
        @endforeach
    </ul>

    <div>{!! $post->body !!}</div>
@endsection