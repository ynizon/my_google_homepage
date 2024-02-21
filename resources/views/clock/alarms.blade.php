
<ul class="no-bullet">
    <li>
        <div>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addModal" id="openAlarm">
                Ajouter une alarme
            </button>
            &nbsp;&nbsp;
            <br/><br/>
        </div>
    </li>
    @foreach($alarms as $alarm)
        <li>
            <div data-alarm-id="{{$alarm->id}}" class="alarm-row">
                @if ($alarm->status) <i class="fa fa-volume-up"></i>@else <i class="fa fa-volume-mute"></i> @endif
                &nbsp;&nbsp;&nbsp;
                @if ($alarm->day1) Lu @endif
                @if ($alarm->day2) Ma @endif
                @if ($alarm->day3) Me @endif
                @if ($alarm->day4) Je @endif
                @if ($alarm->day5) Ve @endif
                @if ($alarm->day6) Sa @endif
                @if ($alarm->day7) Di @endif
                - {{sprintf("%02d",$alarm->hour)}}:{{sprintf("%02d",$alarm->minute)}}
            </div>
        </li>
    @endforeach
</ul>

<script>
    $(".alarm-row").unbind( "click" );
    $(".alarm-row").click(function () {
        $.ajax({
            type: "GET",
            url: '/clock/load/'+$(this).data("alarm-id"),
            success: function(data)
            {
                $('#deleteAlarm').show();
                $('#addModal').modal('show');
                $("#hour").val(data.hour);
                $("#minute").val(data.minute);
                $("#alarm_id").val(data.alarm_id);
                $("#sound").val(data.sound);
                $("#day1").prop('checked', Boolean(data.day1));
                $("#day2").prop('checked', Boolean(data.day2));
                $("#day3").prop('checked', Boolean(data.day3));
                $("#day4").prop('checked', Boolean(data.day4));
                $("#day5").prop('checked', Boolean(data.day5));
                $("#day6").prop('checked', Boolean(data.day6));
                $("#day7").prop('checked', false);
                $("#status").prop('checked', Boolean(data.status));
                $("#status-block").show();
            }
        });
    });

    alarms = [];
    @foreach($alarms as $alarm)
        @if ($alarm->status)
            alarms.push({
                day1: {{$alarm->day1}},
                day2: {{$alarm->day2}},
                day3: {{$alarm->day3}},
                day4: {{$alarm->day4}},
                day5: {{$alarm->day5}},
                day6: {{$alarm->day6}},
                day7: {{$alarm->day7}},
                hour: {{$alarm->hour}},
                minute: {{$alarm->minute}},
                sound: '{{$alarm->sound}}'
            });
        @endif
    @endforeach
</script>
