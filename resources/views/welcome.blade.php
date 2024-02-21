@extends('layouts.app')

@section('content')
    <div class="mdl-grid">
        <div class="mdl-cell mdl-cell--12-col">
            <form method="get" action="https://www.google.fr/search" style="margin:auto;text-align:center">
                <br/><br/><br/>
                <input type="text" name="q" id="q" value="" style="padding:5px;width:200px" />&nbsp;&nbsp;
                <input type="submit" name="Rechercher"  style="cursor:pointer;padding:5px" value="Rechercher" />
            </form>
            <br/><br/>
            <div>
                <div style="@if (count($events)>0) float:left;width:50%;text-align:center; @endif">
                    @if (!empty($photo))
                        <a href='https://photos.google.com/search/{{$photo['filename']}}'><img class='myimg' src='/picture?filename={{ $photo['filename'] }}'></a>
                    @endif
                </div>
                <div style="@if (count($events)>0) float:left;width:50%; @endif">
                    <ul>
                        @if (count($events)>0)
                            <li style="font-weight: bold;text-align: left;list-style: none;padding-bottom: 20px;)">Planning de la semaine: </li>
                        @endif
                        @foreach ($events as $event)
                            <li style="text-align: left;{{ $event["css"] }}">
                                {{ $event["day"] }} {{ $event["date"] }} {{ $event["hour"] }} - {{ $event["summary"] }} {{ $event["frequency"] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <br style="clear:both"/><br/>
            <div style="text-align: center">
                @if (count($albums)>0)
                    Choisir uniquement des photos dans
                    <form method="post" id="change_album" action="/changeAlbum">
                        {{csrf_field()}}
                        <select name="album_id" id="album_id" onchange="changeAlbum()">
                            <option value="-">toutes les photos</option>
                            @foreach ($albums as $albumId => $albumTitle)
                                <option @if ($albumId == $albumIdSelected) selected @endif value="{{ $albumId }}">{{ $albumTitle }}</option>
                            @endforeach
                        </select>
                    </form>&nbsp;
                    &nbsp;
                    <script>
                        function changeAlbum(){
                            document.getElementById('change_album').submit();
                        }
                    </script>
                @endif

                <span id="refresh_pictures"></span>
                <span id="refresh_error"></span>
                <br/>
            </div>
            <script>
                document.getElementById("q").focus();
                var refreshing = false;

                fetch('/refreshEmails', {})
                    .then(response => response.text())
                    .then(body => {
                        eval(body);
                });

                window.setInterval(function() {
                    document.getElementById('refresh_pictures').innerText = '';
                    if (document.getElementById('refresh_error').innerText === '' && !refreshing) {
                        document.getElementById('refresh_error').innerText = '';
                        refreshing = true;
                        fetch('/refresh', {})
                            .then(response => response.text())
                            .then(body => {
                                refreshing = false;
                                eval(body);
                        });
                    }
                }, 5000);

            </script>
        </div>
    </div>
@endsection
