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

class ControllerNONOPTIM extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $code = '';

    public function refresh(Request $request) {

        $cacheFilename = isset($_COOKIE['cacheFilename']) ? $_COOKIE['cacheFilename'] : 'cache.json';
        if (file_exists(storage_path($cacheFilename))) {
            $json = json_decode(file_get_contents(storage_path($cacheFilename)), true);
            $dirPictures = storage_path("pictures_".$cacheFilename);
            $listPhotos = $json['pictures'];

            $events = $this->getEvents();
            $json = ["pictures_cache" => $json['pictures_cache'],"pictures" => $json['pictures'], "events" => $events,
                "albums" => $json['albums'], "date"=>$json['date']];
            file_put_contents(storage_path($cacheFilename), json_encode($json));

            if (!file_exists($dirPictures)) {
                mkdir($dirPictures);
            }

            $downloadMax = 1;
            $download = 0;
            $photosLibraryClient = $this->getPhotoLibraryClient();
            if (count($listPhotos) > 0) {
                foreach ($listPhotos as $photo) {
                    if ($download < $downloadMax) {
                        $mediaItem = $photosLibraryClient->getMediaItem($photo["id"]);
                        $urlPhoto = $mediaItem->getBaseUrl() . "=w500-h400";
                        $filename = $mediaItem->getFilename();
                        if (stripos($filename,".mp4") === false) {
                            if (!file_exists($dirPictures . "/" . $filename)) {
                                file_put_contents($dirPictures . "/" . $filename, file_get_contents($urlPhoto));
                                $download++;
                            }
                        }
                    }
                }
            }

            $nbFiles = 0;
            $files = scandir($dirPictures);
            foreach ($files as $filename) {
                if ($filename != "." && $filename != "..") {
                    $json["pictures_cache"][] = $filename;
                    $nbFiles++;
                }
            }

            file_put_contents(storage_path($cacheFilename), json_encode($json));
            echo $nbFiles."/".count($listPhotos);
        }
    }

    /**
     * @throws ValidationException
     * @throws ApiException
     */
    public function main(){
        $user = User::find(1);

        $now = date("Y-m-d");
        $cacheFilename = isset($_COOKIE['cacheFilename']) ? $_COOKIE['cacheFilename'] : 'cache.json';

        //Choix de l'album
        $albumIdSelected = '';
        if (isset($_GET["album_id"])) {
            setcookie("album_id", $_GET["album_id"], time() + (10 * 365 * 24 * 60 * 60));
            if (file_exists(storage_path($cacheFilename))) {
                unlink(storage_path($cacheFilename));
            }
            $dirPictures = storage_path("pictures_".$cacheFilename);
            if (file_exists($dirPictures)) {
                array_map('unlink', glob("$dirPictures/*.*"));
                rmdir($dirPictures);
            }
            header("location: /main");
            exit();
        }
        if (isset($_COOKIE["album_id"])) {
            $albumIdSelected = $_COOKIE["album_id"];
        }

        if (isset($_COOKIE["cacheFilename"])) {
            $cacheFilename = $_COOKIE["cacheFilename"];
        } else {
            $cacheFilename = md5($user->email);
            setcookie("cacheFilename", $cacheFilename, time() + (10 * 365 * 24 * 60 * 60));
        }

        $albums = [];
        $expired = false;
        if (file_exists(storage_path($cacheFilename))) {
            $json = json_decode(file_get_contents(storage_path($cacheFilename)), true);
            //Rafraichit les photos chaque mois (necessite reconnexion)
            if (date('Y-m-d', strtotime($json["date"] . ' + 30 days')) < date('Y-m-d')){
                $expired = true;
                $dirPictures = storage_path("pictures_".$cacheFilename);
                if (file_exists($dirPictures)) {
                    array_map('unlink', glob("$dirPictures/*.*"));
                    rmdir($dirPictures);
                }
                unset($_COOKIE["cacheFilename"]);
                setcookie('cacheFilename', '', -1, '/');
                header('Location: /' );
                exit();
            }
        }

        if ($expired or !file_exists(storage_path($cacheFilename))) {
            // Set up the Photos Library Client that interacts with the API
            $photosLibraryClient = $this->getPhotoLibraryClient();

            //Liste des albums
            $albumsPhotos = $photosLibraryClient->listAlbums();

            foreach ($albumsPhotos as $album) {
                $albums[$album->getId()] = $album->getTitle();
            }

            //Liste des photos de l'album
            $listPhotos = [];
            if (empty($albumIdSelected)) {
                $response = $photosLibraryClient->searchMediaItems();
            } else {
                $response = $photosLibraryClient->searchMediaItems(['albumId' => $albumIdSelected]);
            }
            foreach ($response->iterateAllElements() as $picture) {
                $listPhotos[] = [ "id"=>$picture->getId()];
            }

            $events = $this->getEvents();
            $json = ["pictures_cache"=> [], "pictures" => $listPhotos, "events" => $events, "albums" => $albums, "date"=>$now];
            file_put_contents(storage_path($cacheFilename), json_encode($json));
        }

        $json = json_decode(file_get_contents(storage_path($cacheFilename)), true);
        $albums = $json["albums"];
        $eventsTmp = $json["events"];
        $events = [];
        foreach ($eventsTmp as $event) {
            $futur = date('Y-m-d', strtotime($now . ' + 11 days'));
            if ($event['date'] >= date("Y-m-d")) {
                if ($event['date'] <= $futur) {
                    if ($event['date'] == date("Y-m-d")) {
                        $event["css"] .= "color:red;";
                        if ($event['hour'] <= date("H:i")) {
                            $event["css"] .= "text-decoration:line-through;";
                        }
                    }
                    $events[] = $event;
                }
            }
        }

        $listCache = $json["pictures_cache"];
        if (count($listCache)>0) {
            shuffle($listCache);
            $photo = ['filename' => $listCache[0]];
        } else {
            $listPhotos = $json["pictures"];

            shuffle($listPhotos);

            $dirPictures = storage_path("pictures_" . $cacheFilename);
            if (!file_exists($dirPictures)) {
                mkdir($dirPictures);
            }

            //Prend la 1er photo
            $filename = "";
            if (count($listPhotos) > 0) {
                try {
                    //Les urls ne durent que 60 minutes, donc il faut les recalculer
                    $mediaItem = $photosLibraryClient->getMediaItem($listPhotos[0]["id"]);
                    $urlPhoto = $mediaItem->getBaseUrl() . "=w500-h400";
                    $filename = $mediaItem->getFilename();
                    file_put_contents($dirPictures . "/" . $filename, file_get_contents($urlPhoto));
                } catch (\Exception $e) {
                    //Do nothing
                    //Cant get picture URL
                    //echo $e->getMessage();
                }
            }
            $photo = ['filename' => $filename];
        }
        return view("/welcome",compact("albums","events","photo", "albumIdSelected"));
    }

    public function home(Request $request) {
        /*
        $this->initSession();

        if (isset($_COOKIE['cacheFilename']) && isset($_COOKIE["refresh_token"])) {
            header('Location: /main');
            exit();
        }else {
            $oAuth = new \App\Models\oAuth2();
            $client = $oAuth->buildClient();

            if (!isset($_GET['code'])) {
                $auth_url = $client->createAuthUrl();
                header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
                exit();
            } else {
                // Exchange the authencation code for a refresh token and access token.
                $client->fetchAccessTokenWithAuthCode($_GET['code']);
                // Add access token and refresh token to seession.
                foreach ($client->getAccessToken() as $key => $value) {
                    $_SESSION[$key] = $value;
                    setcookie($key, $value, time() + (10 * 365 * 24 * 60 * 60));
                }

                if (!isset($_COOKIE["refresh_token"])) {
                    try {
                        $client = new Google_Client();
                        $client->setAuthConfig(storage_path('app/google-calendar/client_secret.json'));
                        $client->revokeToken($_COOKIE['access_token']);
                    }catch(\Exception $e){
                        //Do nothing
                    }
                    echo '<meta http-equiv="refresh" content="0;URL=/">';
                    exit();
                }
                //Redirect back to main script
                $redirect_uri = str_replace("connect.php", "main", $client->getRedirectUri());
                header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
            }
        }
        */
        return redirect("/main");
    }

    public function tos() {
        return view("/tos");
    }

    public function privacy() {
        return view("/privacy");
    }

    public function logout() {
        try {
            $client = new Google_Client();
            $client->setAuthConfig(storage_path('app/google-calendar/client_secret.json'));
            $client->revokeToken($_SESSION['access_token']);
        } catch(\Exception $e){
            //Nothing
        }

        unset($_SESSION);
        $cacheFilename = isset($_COOKIE['cacheFilename']) ? $_COOKIE['cacheFilename'] : 'cache.json';
        if (file_exists(storage_path($cacheFilename))) {
            unlink(storage_path($cacheFilename));
        }
        foreach (['code', 'album_id', 'cacheFilename','access_token','refresh_token','homepage_session'] as $cookie) {
            if (isset($_COOKIE[$cookie])) {
                unset($_COOKIE[$cookie]);
                setcookie($cookie, '', -1, '/');
            }
        }

        return redirect("/?logout");
    }

    private function getEvents() {
        //Liste des evenements (-> .env GOOGLE_CALENDAR_ID)
        $eventsTmp = Event::get();
        $events = [];
        foreach ($eventsTmp as $item) {
            if (!empty($item->__get("summary"))) {
                $reccurence = '';//$item->getRecurrence();
                $hour = '';
                if ($item->getSortDate() !== null) {
                    $date = substr($item->getSortDate(), 0, 10);
                    $hour = substr($item->getSortDate(), 11, 5);
                } else {
                    $date = $item->getSortDate();
                }

                $events[] = ["date"=>$date ,"hour" => $hour ,"frequency"=> $reccurence ,
                    "summary" => $item->__get("summary"), "css"=>""];

                /*
                if (isset($item["recurrence"])) {
                    $reccurence = " (RECURRENT)";
                    $dateMin = $date;
                    $dateRecurence = '';
                    if (stripos($item["recurrence"][0], "WEEKLY") !== false) {
                        while ($dateMin <= $futur) {
                            $dateMin = date('Y-m-d', strtotime($dateMin . ' + 7 days'));
                            if ($dateRecurence == '' && $dateMin >= $now) {
                                $dateRecurence = $dateMin;
                                $date = $dateRecurence;
                            }
                        }
                    }
                    if (stripos($item["recurrence"][0], "MONTHLY") !== false) {
                        while ($dateMin <= $futur) {
                            $dateMin = date('Y-m-d', strtotime($dateMin . ' + 1 month'));
                            if ($dateRecurence == '' && $dateMin >= $now) {
                                $dateRecurence = $dateMin;
                                $date = $dateRecurence;
                            }
                        }
                    }
                    if (stripos($item["recurrence"][0], "YEARLY") !== false) {
                        while ($dateMin < $futur) {
                            $dateMin = date('Y-m-d', strtotime($dateMin . ' + 1 year'));
                            if ($dateRecurence == '' && $dateMin >= $now) {
                                $dateRecurence = $dateMin;
                                $date = $dateRecurence;
                            }
                        }
                    }
                }
                $events[] = $date . $hour . $reccurence . " - " . $item->__get("summary");
                */

            }
        }

        $eventsTmp = $events;
        $events = [];
        foreach ($eventsTmp as $event) {
            $date = $event["date"];
            $tabDate = explode('-', $date);
            if (count($tabDate) > 2) {
                $timestamp = mktime(0, 0, 0, $tabDate[1], $tabDate[2], $tabDate[0]);
                $jour = date('D', $timestamp);
                switch ($jour) {
                    case 'Mon':
                        $jour = 'Lundi';
                        break;
                    case 'Tue':
                        $jour = 'Mardi';
                        break;
                    case 'Wed':
                        $jour = 'Mercredi';
                        break;
                    case 'Thu':
                        $jour = 'Jeudi';
                        break;
                    case 'Fri':
                        $jour = 'Vendredi';
                        break;
                    case 'Sat':
                        $jour = 'Samedi';
                        break;
                    case 'Sun':
                        $jour = 'Dimanche';
                        break;
                }

                $event["day"] = $jour;

                $events[] = $event;
            }
        }

        return $events;
    }

    public function picture(Request $request){
        $cacheFilename = isset($_COOKIE['cacheFilename']) ? $_COOKIE['cacheFilename'] : 'cache.json';
        $dirPictures = storage_path("pictures_".$cacheFilename);
        $filename = $request->input("filename");
        if (file_exists($dirPictures."/".$filename) && !empty($filename)) {
            $content = file_get_contents($dirPictures."/".$filename);
        } else {
            $content = file_get_contents(base_path("/public/screenshot.jpg"));
        }
        header('Content-type:image/jpeg');
        if (stripos($filename,".png")) {
            header('Content-type:image/png');
        }
        if (stripos($filename,".mp4")) {
            header('Content-type:video/mp4');
        }
        return $content;
    }

    public function getPhotoLibraryClient()
    {
        $clientSecretJson = json_decode(
            file_get_contents(storage_path('app/google-calendar/client_secret.json')),
            true
        )['web'];

        $clientId = $clientSecretJson['client_id'];
        $clientSecret = $clientSecretJson['client_secret'];

        $scopes = ['https://www.googleapis.com/auth/photoslibrary.readonly',
            'https://www.googleapis.com/auth/calendar.readonly',
            'https://www.googleapis.com/auth/userinfo.email'];

        $user = User::find(1);
        $token = json_decode($user->google_access_token_json, true);
        $authCredentials = new UserRefreshCredentials(
            $scopes,
            [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $token["refresh_token"]
            ]
        );
        return new PhotosLibraryClient(['credentials' => $authCredentials]);
    }




    public function getUserClient(){
        $user = User::where('id', '=', 1)->first();

        /**
         * Strip slashes from the access token json
         * if you don't strip mysql's escaping, everything will seem to work
         * but you will not get a new access token from your refresh token
         */
        $accessTokenJson = stripslashes($user->google_access_token_json);

        /**
         * Get client and set access token
         */
        $client = $this->getClient();
        $client->setAccessToken($accessTokenJson);

        /**
         * Handle refresh
         */
        if ($client->isAccessTokenExpired()) {
            // fetch new access token
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $client->setAccessToken($client->getAccessToken());

            // save new access token
            $user->google_access_token_json = json_encode($client->getAccessToken());
            $user->save();
        }

        return $client;
    }

    public function preconnection(){

        $client = $this->getClient();

        /**
         * Generate the url at google we redirect to
         */
        $scopes = [
            \Google\Service\Oauth2::USERINFO_PROFILE,
            \Google\Service\Oauth2::USERINFO_EMAIL,
            \Google\Service\Oauth2::OPENID,
            \Google\Service\Drive::DRIVE_METADATA_READONLY,
            \Google\Service\Oauth2::OPENID,
            'https://www.googleapis.com/auth/calendar.readonly',
            'https://www.googleapis.com/auth/photoslibrary.readonly',
        ];

        $authUrl = $client->createAuthUrl();
        header("location: ".$authUrl) ;
    }

    public function connect(Request $request) {
        $user = Auth::user();
        $user->provider_name ='google';
        $client = $this->getClient();
        $authCode = $request->input("code");
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $user->google_access_token_json = json_encode($accessToken);
        $user->save();
    }

    public function dashboard(Request $request) {

        return view('dashboard');
    }

    /**
     * Gets a google client
     *
     * @return \Google_Client
     */
    private function getClient():\Google_Client
    {
        // load our config.json that contains our credentials for accessing google's api as a json string
        //$configJson = base_path().'/config.json';
        $configJson = storage_path('app/google-calendar/client_secret.json');

        // define an application name
        $applicationName = config("app.url");

        // create the client
        $client = new \Google_Client();
        $client->setApplicationName($applicationName);
        $client->setAuthConfig($configJson);
        $client->setAccessType('offline'); // necessary for getting the refresh token
        $client->setApprovalPrompt('force'); // necessary for getting the refresh token
        // scopes determine what google endpoints we can access. keep it simple for now.
        $client->setScopes(
            [
                \Google\Service\Oauth2::USERINFO_PROFILE,
                \Google\Service\Oauth2::USERINFO_EMAIL,
                \Google\Service\Oauth2::OPENID,
                \Google\Service\Drive::DRIVE_METADATA_READONLY,
                \Google\Service\Oauth2::OPENID,
                'https://www.googleapis.com/auth/calendar.readonly',
                'https://www.googleapis.com/auth/photoslibrary.readonly',
            ]
        );
        $client->setRedirectUri(config("app.url")."/connect.php");
        $client->setIncludeGrantedScopes(true);
        return $client;
    } // getClient
}
