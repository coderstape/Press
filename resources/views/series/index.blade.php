@extends('press::layout')

@section('content')

    {{-- Restored: the authors/ and series/ view directories shipped
         with their contents SWAPPED -- this file held the authors
         markup (undefined $authors fatal) and the real series markup
         sat in authors/index. Neither page had ever rendered. --}}
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
