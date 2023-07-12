<?php

namespace App\Http\Controllers;

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

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected Helper $helper;

    public function __construct(Helper $helper){
        $this->helper = $helper;
    }

    public function refresh(Request $request) {
        $user = $this->helper->getTheUser();
        $cacheFilename = $this->helper->getCacheDirFilename($user->id);

        $nbFiles = '0';
        $listPhotos = '0';
        $error = '';
        if (file_exists($cacheFilename)) {
            $json = json_decode(file_get_contents($cacheFilename), true);

            $events = $this->helper->getEvents();
            $json = ["pictures_cache" => $json['pictures_cache'],"pictures" => $json['pictures'], "events" => $events,
                "albums" => $json['albums'], "date"=>$json['date']];
            file_put_contents($cacheFilename, json_encode($json));

            $albums = $this->helper->getAlbums();
            if (count($albums)>0) {
                $json['albums'] = $albums;
            }
            $json = ["pictures_cache" => $json['pictures_cache'],"pictures" => $json['pictures'], "events" => $events,
                "albums" => $json['albums'], "date"=>$json['date']];
            file_put_contents($cacheFilename, json_encode($json));

            $info = $this->helper->downloadMedia($user->id, $json, $cacheFilename);

            $nbFiles = $info['nbFiles'];
            $listPhotos = $info['listPhotos'];
            $error = $info['error'];
        }

        return view('refresh', compact('nbFiles', 'listPhotos', 'error'));
    }

    public function changeAlbum(Request $request) {
        $user = $this->helper->getTheUser();
        if (!empty($request->input("album_id"))){
            if ($user->album_id != $request->input("album_id")){
                $this->helper->removePictures($user->id);
                $cacheFilename = $this->helper->getCacheDirFilename($user->id);
                if (file_exists($cacheFilename)) {
                    $json = json_decode(file_get_contents($cacheFilename), true);
                    $pictures = $this->helper->getPictures();
                    $json = ["pictures_cache" => [],"pictures" => $pictures, "events" => $json['events'],
                        "albums" => $json['albums'], "date"=>$json['date']];
                    file_put_contents($cacheFilename, json_encode($json));
                }
            }
            $user->album_id = $request->input("album_id");
        }
        $user->save();

        return redirect("/main");
    }

    public function main(){
        $user = $this->helper->getTheUser();
        $albumIdSelected = $user->album_id;
        $this->helper->refreshInfos($user->id);
        $infos = $this->helper->readInfos($user->id);

        return view("/welcome",["albums" =>$infos["albums"],"events"=>$infos["events"],"photo"=>$infos["photo"],
            "albumIdSelected"=>$albumIdSelected]);
    }

    public function root(Request $request) {
        $authEmail = Cookie::get("auth_email");
        $authPassword = Cookie::get("auth_password");
        if (empty($authEmail) || empty($authPassword)) {
            return redirect("/login");
        }

        $user = User::where("email",$authEmail)->where("password",$authPassword)->first();
        if ($user) {
            Auth::login($user);
            if (empty($user->provider_name)) {
                return redirect("/dashboard");
            }
            return redirect("/main");
        }
    }

    public function tos() {
        return view("/tos");
    }

    public function privacy() {
        return view("/privacy");
    }

    public function removeAll() {
        $user = $this->helper->getTheUser();
        if ($user) {
            $this->helper->removeAll($user->id);
        }

        foreach ($_COOKIE as $cookie) {
            Cookie::queue(Cookie::forget($cookie));
        }
        return redirect('login')->with(Auth::logout());
    }

    public function picture(Request $request){
        $user = $this->helper->getTheUser();
        $filename = $request->input("filename");
        return $this->helper->getPicture($filename, $user->id);;
    }

    public function connect(Request $request) {
        $user = $this->helper->getTheUser();
        $client = $this->helper->getClient();
        $authCode = $request->input("code");
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $this->helper->updateUserToken($user->id, $accessToken);
        return redirect("/");
    }

    public function goGoogle(){
        $user = $this->helper->getTheUser();
        $client = $this->helper->getClient($user->id);

        /**
         * Generate the url at google we redirect to
         */
        $scopes = [
            \Google\Service\Oauth2::USERINFO_PROFILE,
            \Google\Service\Oauth2::USERINFO_EMAIL,
            \Google\Service\Oauth2::OPENID,
            \Google\Service\Calendar::CALENDAR_READONLY,
            \Google\Service\Oauth2::OPENID,
            'https://www.googleapis.com/auth/photoslibrary.readonly',
        ];

        $authUrl = $client->createAuthUrl($scopes, ['login_hint'=>$user->email]);
        header("location: ".$authUrl) ;
    }

    public function dashboard(Request $request) {
        $user = $this->helper->getTheUser();
        $email = $user->email;
        return view('dashboard', compact('email'));
    }
}
