@extends('layouts.empty')

@section('content')
    document.getElementById('unreadEmails').innerText = "{{$nbEmails}}";
    document.getElementById('unreadEmails').title = "{{$emailsError}}";
    @if(!empty($nbEmails))
        document.getElementById('unreadEmails').style.display = 'block';
    @endif
@endsection
