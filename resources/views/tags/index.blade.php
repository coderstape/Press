@extends('press::layout')

@section('content')

    <h1>Tags</h1>

    <ul>
        @foreach ($tags as $tag)
            <li>
                <a href="{{ $tag->path() }}">
                    {{ $tag->name }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection