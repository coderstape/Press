@extends('press::layout')

@section('content')

    {{-- Was a copy of the series show view referencing an undefined
         $series -- the controller passes only $author, so this page
         had never rendered (same incident as the authors index).
         Content mirrors the sibling views; judgment call, veto ok. --}}
    <h1>{{ $author->name }}</h1>
    <p><a href="{{ url(Press::path() . '/posts') }}">All posts</a></p>

    <h3>Posts</h3>
    <ul>
        @foreach ($author->activePosts as $post)
            <li>
                <a href="{{ $post->path() }}">
                    {{ $post->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection
