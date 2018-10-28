@extends('larapress::layout')

@section('content')

    <h1>Posts</h1>

    <ul>
        @foreach ($posts as $post)
            <li>
                <a href="{{ $post->path() }}">
                    <img src="{{ $post->image() }}" alt="Cover image for the post {{ $post->title }}" class="w-10 h-10">
                    {{ $post->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection