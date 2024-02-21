<ul class="no-bullet">
    <li>
        <div>
            <select name="radio" id="changeRadio" class="form-control btn">
                <option value="-">-</option>
                @foreach($radios as $radio => $url)
                    <option value="{{$url}}">{{$radio}}</option>
                @endforeach
            </select>
            <br/><br/>
        </div>
    </li>
    @foreach($events as $event)
        <li>
            <div class="event-row">
                {{$event['hour']}} : {{$event['summary']}}
            </div>
        </li>
    @endforeach
</ul>

<script>

    $("#changeRadio").change(function(){
        let player = document.getElementById('radioPlayer');
        //player.src = '/player?url='+$("#changeRadio").val();
        player.src = $("#changeRadio").val();

        if ($("#changeRadio").val() == "-") {
            $('.button-stop').click();
        } else {
            $('.button-play').click();
        }
    });

    events = [];
    @foreach($events as $event)
        events.push({
            event : "{{str_replace("00:00","",$event['hour'])}}, {!!  htmlspecialchars_decode($event['summary'])!!}",
            title: "{{$event['summary']}}",
            hour: "{{$event['hour']}}"
        });
    @endforeach
</script>
