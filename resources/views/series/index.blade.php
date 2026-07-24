@extends('press::layout')

@section('content')

    {{-- Restored: the authors/ and series/ view directories shipped
         with their contents SWAPPED -- this file held the authors
         markup (undefined $authors fatal) and the real series markup
         sat in authors/index. Neither page had ever rendered. --}}
    <h1>Series</h1>

    {{-- $series is the COLLECTION the controller passes; the loop
         must not reuse the name. It happened to work (foreach
         evaluates its subject once) but left $series pointing at the
         last item for anything added after the loop. --}}
    <ul>
        @foreach ($series as $item)
            <li>
                <a href="{{ $item->path() }}">
                    {{ $item->title }}
                </a>
            </li>
        @endforeach
    </ul>
@endsection
