@extends('layouts.app')

@section('content')
    <div class="mdl-grid">
        <div class="mdl-cell mdl-cell--12-col">
            <br/>
            {{$email}}, pour visualiser votre calendrier, et vos images, vous devez autoriser l'application à accéder à votre
            compte Google. Pour cela, <a href="/goGoogle">cliquez ici</a>.
        </div>
    </div>
@endsection
