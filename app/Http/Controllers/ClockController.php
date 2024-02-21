<?php

namespace App\Http\Controllers;

use App\Models\Alarm;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Auth\OAuth2;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Spatie\GoogleCalendar\Event;

use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;
use Illuminate\Support\Facades\Cookie;
use App\Helpers\Helper;

class ClockController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected Helper $helper;

    public function __construct(Helper $helper){
        $this->helper = $helper;
    }

	public function clock() {
        $radios = Alarm::RADIOS;
        $sounds = [];

        return view("clock", compact("radios", "sounds"));
	}

    public function player(Request $request) {
        $url = $request->input('url');
        if ($url != '' &&  $url != '-') {
            $filePath = $url;
            set_time_limit(0);

            $strContext = stream_context_create(
                array(
                    'http' => array(
                        'method' => 'GET',
                        'header' => "Accept-language: en\r\n"
                    )
                )
            );
            $fpOrigin = fopen($filePath, 'rb', false, $strContext);
            header('content-type: application/octet-stream');
            while (!feof($fpOrigin)) {
                $buffer = fread($fpOrigin, 4096);
                echo $buffer;
                flush();
            }
            fclose($fpOrigin);
        }
	}

    public function alarms(Request $request) {
        $alarms = [];
        if (Auth::user()) {
            $alarms = Auth::user()->alarms;
        }

        return view("clock/alarms",compact("alarms"));
    }

    public function events(Request $request) {
        $radios = Alarm::RADIOS;
        $events = [];
        if (Auth::user()) {
            $eventsTmp = $this->helper->getEvents();
            foreach ($eventsTmp as $event) {
                if ($event['date'] == date("Y-m-d")) {
                    $events[] = $event;
                }
            }
        }

        return view("clock/events",compact("events","radios"));
    }

    public function saveAlarm(Request $request) {
        if (Auth::user()) {
            $alarm = new Alarm();
            $id = $request->input("alarm_id");
            if ($id > 0) {
                $alarm = Alarm::find($id);
                if ($alarm->user_id != Auth::user()->id) {
                    $alarm = new Alarm();
                }
            }

            for ($nbJour = 1; $nbJour < 8; $nbJour++) {
                $field = "day".$nbJour;
                $alarm->$field = 0;
            }
            if ($request->has("day")) {
                $days = $request->input("day");
                foreach ($days as $day) {
                    $field = "day" . $day;
                    $alarm->$field = 1;
                }
            }

            if ($request->has("status")) {
                $alarm->status = 1;
            } else {
                $alarm->status = 0;
            }

            $alarm->sound = $request->input("sound");
            $alarm->hour = (int) $request->input("hour");
            $alarm->minute = (int) $request->input("minute");
            $alarm->user_id = Auth::user()->id;
            $alarm->save();
        }
    }

    public function deleteAlarm(Request $request) {
        $id = $request->input("alarm_id");
        if ($id > 0) {
            $alarm = Alarm::find($id);
            if ($alarm->user_id == Auth::user()->id) {
                $alarm->delete();
            }
        }
    }

    public function loadAlarm($id) {
        if ($id > 0) {
            $alarm = Alarm::find($id);
            if ($alarm->user_id == Auth::user()->id) {
                return response()->json([
                    'hour' => $alarm->hour,
                    'minute' => $alarm->minute,
                    'sound' => $alarm->sound,
                    'day1' => $alarm->day1,
                    'day2' => $alarm->day2,
                    'day3' => $alarm->day3,
                    'day4' => $alarm->day4,
                    'day5' => $alarm->day5,
                    'day6' => $alarm->day6,
                    'day7' => $alarm->day7,
                    'status' => $alarm->status,
                    'alarm_id' => $alarm->id,
                ]);
            }
        }
    }
}
