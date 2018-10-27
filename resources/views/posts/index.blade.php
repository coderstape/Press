@extends('larapress::layout')

@section('content')

    <h1>Posts</h1>

    <ul>
        @foreach ($posts as $post)
            <li>
                <a href="{{ $post->path() }}">
                    {{ $post->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection