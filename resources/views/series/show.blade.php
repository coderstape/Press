@extends('press::layout')

@section('content')

    <h1>AUTHORS: {{ $author->name }}</h1>
    <p><a href="{{ url(config('press.path') . '/posts') }}">All posts</a></p>

    <h3>Authors</h3>
    <ul>
        @foreach ($author->posts as $post)
            <li>
                <a href="{{ $post->path() }}">
                    {{ $post->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection