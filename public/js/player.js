let alarms = [];
let isSilent = true;
let goEnabled = false;
let lastAlarm = '';

function waveAfterWave() {
    $('.wave').each(function () {
        height = $(this).height();
        $(this).css('height', height);
    });
    $('.wave').addClass('no-animation');
};

$( document ).ready(function() {
    let player = document.getElementById('radioPlayer');
    let radioVolume = document.getElementById('radioVol');
    let userVolume = 50;

    //Reload events each hour
    window.setInterval(function() {
        refreshEvents();
        refreshWeather();
    }, 3600000);

    //Detect alarms each minute
    window.setInterval(function() {
        let now = new Date();
        let hours = now.getHours();
        if (hours <10) {
            hours = "0"+hours;
        }
        let minutes = now.getMinutes();
        if (minutes <10) {
            minutes = "0"+minutes;
        }
        $(".radio-name").html(hours + ':'+minutes);
        let launchAlarm = false;
        for (let numAlarm = 0; numAlarm < alarms.length; numAlarm++) {
            let alarm = alarms[numAlarm];
            if ((alarm.day1 && now.getDay() === 1) ||
                (alarm.day2 && now.getDay() === 2) ||
                (alarm.day3 && now.getDay() === 3) ||
                (alarm.day4 && now.getDay() === 4) ||
                (alarm.day5 && now.getDay() === 5) ||
                (alarm.day6 && now.getDay() === 6) ||
                (alarm.day7 && now.getDay() === 0))
            {
                if (now.getHours() == alarm.hour && now.getMinutes() == alarm.minute  && goEnabled &&
                    lastAlarm != now.getDay() +':'+hours + ':'+minutes) {
                    lastAlarm = now.getDay()+':'+hours + ':'+minutes;
                    launchAlarm = true;
                    //player.src = '/player?url='+alarm.sound;
                    player.src = alarm.sound;
                }
            }
        }
        if (launchAlarm && isSilent && goEnabled) {
            let msg = readEvents();
            msg.addEventListener("end", () => {
                $('.button-play').click();
                isSilent = false;
                $('#stopModal').modal('show');
            });
        }
    },1000);

    function readEvents() {
        let now = new Date();
        let textHour = 'Il est ';
        if (now.getMinutes() == 0){
            textHour = textHour +' '+now.getHours() + 'heures. ';
        } else {
            textHour = textHour +' '+now.getHours() + 'heures '+now.getMinutes()+'. ';
        }

        let textToRead = '';
        for (let numEvent = 0; numEvent < events.length; numEvent++) {
            let event = events[numEvent];
            textToRead = textToRead +' ' +event.event+'. ';
        }
        let msg = new SpeechSynthesisUtterance();
        if (textToRead === '') {
            textToRead = textHour + "Aucun évènement n'est prévu aujourd'hui. ";
        } else {
            textToRead = textHour + "Voici les évènement prévus aujourd'hui. "+textToRead;
        }
        textToRead = textToRead + $("#meteo").html();
        msg.lang = "fr-FR";
        msg.text = textToRead;
        window.speechSynthesis.speak(msg);
        return msg;
    }

    function refreshWeather() {
        let city = 'Saint-Herblain,fr';

        fetch(`https://api.openweathermap.org/data/2.5/weather?units=metric&lang=fr&q=`+city+`&appid=b9df6c50feb47582d0f93faaef2b1c4e`)
            .then(response => {
                return response.json()
            })
            .then(data => {
                $("#meteo").html("Le temps sera "+data.weather[0].description+ " avec une témpérature comprise entre "+parseInt(data.main.temp_min) + ' et '+ parseInt(data.main.temp_max) +" degré. ");
                //weatherIcon.classList.add(`owf-${data.weather[0].id}`);
            })
            .catch(err => {
            })
    }


    function stopAlarm() {
        $('.button-stop').click();
        isSilent = true;
    }

    $('.button-play').click(function () {
        $('#player').show();
        let icon = $(this).find('i');
        if (icon.hasClass('fa-pause')) {
            icon.removeClass('fa-pause');
            icon.addClass('fa-play');

            player.pause();
            waveAfterWave();
        } else {
            icon.removeClass('fa-play');
            icon.addClass('fa-pause');
            player.play();
            $('.wave').removeClass('no-animation');
        }
    });

    $('.button-stop').click(function () {
        $("#changeRadio").val("-");
        let icon = $('.button-play').find('i');
        $('#player').hide();
        if (icon.hasClass('fa-pause')) {
            icon.removeClass('fa-pause');
            icon.addClass('fa-play');
            player.src = "";
        }
        waveAfterWave();
    });

    $('.button-sound').click(function () {
        let icon = $(this).find('i');

        if (icon.hasClass('fa-volume-off')) {
            radioVolume.value = userVolume;
        } else {
            radioVolume.value = 0;
        }
        setVolume();
    });

    $('#radioVol').on('input', function() {
        setVolume();
    });
    $('#radioVol').on('change', function() {
        setVolume();
    });

    function setVolume() {
        player.volume = radioVolume.value/100;
        checkVolume();
    };

    function checkVolume() {
        let icon = $('.button-sound').find('i');

        if (radioVolume.value == 0) {
            icon.removeClass('fa-volume-up');
            icon.removeClass('fa-volume-down');
            icon.addClass('fa-volume-off');
        } else if (radioVolume.value < 50) {
            icon.removeClass('fa-volume-off');
            icon.removeClass('fa-volume-up');
            icon.addClass('fa-volume-down');
            userVolume = radioVolume.value;
        } else {
            icon.removeClass('fa-volume-off');
            icon.removeClass('fa-volume-down');
            icon.addClass('fa-volume-up');
            userVolume = radioVolume.value;
        }
    };

    $("#addAlarm").click(function () {
        $.ajax({
            type: "POST",
            url: '/clock/save',
            data: $('#frmAddModal').serialize(),
            success: function(data)
            {
                refreshAlarms()
            }
        });
    });

    function refreshAlarms() {
        $.ajax({
            type: "GET",
            url: '/clock/alarms',
            success: function(data)
            {
                $("#alarms").html(data);

                $("#openAlarm").click(function () {
                    $('#alarm_id').val(0);
                    $('#status').prop('checked', true);
                    $('#deleteAlarm').hide();
                    $('#status-block').hide();
                });
            }
        });
    }

    function refreshEvents() {
        $.ajax({
            type: "GET",
            url: '/clock/events',
            success: function(data)
            {
                $("#events").html(data);
            }
        });
    }

    $("#deleteAlarm").click(function () {
        $.ajax({
            type: "POST",
            url: '/clock/delete',
            data: $('#frmAddModal').serialize(),
            success: function(data)
            {
                refreshAlarms()
            }
        });
    });

    $("#goModal").on("hidden.bs.modal", function () {
        goEnabled = true;
        openFullscreen();
    });

    $("#goModalTitle").on("click", function () {
        $('#goModal').modal('hide');
        goEnabled = true;
        openFullscreen();
    });

    $("#stopModal").on("hidden.bs.modal", function () {
        stopAlarm();
    });

    $("#stopModalTitle").on("click", function () {
        $('#stopModal').modal('hide');
        stopAlarm();
    });

    function openFullscreen() {
        var elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) { /* Safari */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { /* IE11 */
            elem.msRequestFullscreen();
        }
    }

    //Start
    $('#goModal').modal('show');
    waveAfterWave();
    refreshAlarms();
    refreshWeather();
    refreshEvents();
});
