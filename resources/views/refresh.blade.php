@extends('layouts.empty')

@section('content')
    @if ($nbFiles == count($listPhotos)-1)
        document.getElementById('refresh_pictures').innerText = '';
        document.getElementById('refresh_error').innerText = '-';
    @else
        document.getElementById('refresh_pictures').innerText = '{{$nbFiles}}/{{count($listPhotos)}}';
        document.getElementById('refresh_error').innerText = '{{$error}}';
    @endif
@endsection
