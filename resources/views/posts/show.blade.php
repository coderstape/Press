@extends('press::layout')

@section('content')

    <h1>{{ $post->title }}</h1>

    @if ($post->series)
        <h2><a href="{{ $post->series->path() }}">{{ $post->series->title }}</a></h2>
    @endif

    <p><a href="{{ url(config('press.path') . '/posts') }}">All posts</a></p>

    <img src="{{ $post->image() }}" alt="Cover image for the post {{ $post->title }}">

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