@extends('press::layout')

@section('content')

    <h1>Series</h1>

    <ul>
        @foreach ($series as $series)
            <li>
                <a href="{{ $series->path() }}">
                    {{ $series->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection