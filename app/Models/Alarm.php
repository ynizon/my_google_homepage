<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Alarm extends Model
{
    public $timestamps = false;

    public const RADIOS = [
        "Europe 1"=> "http://stream.europe1.fr/europe1.mp3",
        "Europe 2"=> "http://europe2.lmn.fm/europe2.mp3",
        "Rire et chansons" => "https://scdn.nrjaudio.fm/adwz2/fr/30401/mp3_128.mp3?origine=fluxradios",
        "RTL " => "http://streaming.radio.rtl.fr/rtl-1-44-128",
        "RTL 2" => "http://streaming.radio.rtl2.fr/rtl2-1-44-128?listen=webCwsBCggNCQgLDQUGBAcGBg",
        "France Info" => "http://direct.franceinfo.fr/live/franceinfo-midfi.mp3",
        "France Inter" => "http://direct.franceinter.fr/live/franceinter-midfi.mp3",
        ];
    public function user()
    {
        return User::where("id","=",$this->user_id)->firstOrFail();
    }
}
