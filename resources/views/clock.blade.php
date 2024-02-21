@extends('layouts.clock')

@section('content')
    <div class="radio-container container">
        <div class="radio-header">
            <div class="row">
                <div class="col-md-12">
                    <div class="radio-name">00:00</div>
                    <div id="meteo" class="current-presenter"></div>
                </div>
            </div>
        </div>
        <div id="player">
            <div class="radio-body">
                <div class="shine"></div>
                <div class="current-song">
                    -
                </div>
                <div class="radio-buttons">
                    <audio id="radioPlayer" src="/player?url=http://icecast.rtl.fr/rtl-1-44-128?listen=webCwsBCggNCQgLDQUGBAcGBg"></audio>
                    <div class="button button-play"><i class="fa fa-play" aria-hidden="true"></i></div>
                    <div class="button button-stop"><i class="fa fa-stop" aria-hidden="true"></i></div>
                </div>
                <div class="wave-bars">
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                    <div class="wave wave-1"></div>
                    <div class="wave wave-2"></div>
                    <div class="wave wave-3"></div>
                    <div class="wave wave-4"></div>
                    <div class="wave wave-5"></div>
                </div>
            </div>
            <div class="radio-volume">
                <div class="button button-sound"><i class="fa fa-volume-up" aria-hidden="true"></i></div>
                <input id="radioVol" class="range" type="range" value="50">
            </div>
        </div>
        <div class="radio-message">
            <div class="message-header">
                <div id="alarms-and-events">
                    <div class="col-sm-1">
                    </div>
                    <div class="col-sm-5">
                        <div id="alarms">
                        </div>
                    </div>
                    <div class="col-sm-5">
                        <div id="events">
                        </div>
                    </div>
                    <div class="col-sm-1">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('clock.add')

    @include('clock.go')

    @include('clock.stop')
@endsection
