@extends('layouts.empty')

@section('content')
    document.getElementById('unreadEmails').innerText = "{{$nbEmails}}";
    document.getElementById('unreadEmails').title = "{{$emailsError}}";
    document.getElementById('unreadEmails').style.display = 'block';
@endsection
