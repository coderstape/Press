@extends('press::layout')

@section('content')

    <h1>Authors</h1>

    <ul>
        @foreach ($authors as $author)
            <li>
                <a href="{{ $author->path() }}">
                    {{ $author->name }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection
