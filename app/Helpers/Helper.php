<?php

namespace App\Helpers;

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

class Helper
{
    public const CLIENT_SECRET = 'app/google-calendar/client_secret.json';
    public function revokeToken($token) {
        try {
            $client = new \Google_Client();
            $client->setAuthConfig(storage_path(self::CLIENT_SECRET));
            $client->revokeToken($token['access_token']);
        } catch(\Exception $e){
            //Nothing
            //echo $e->getMessage();
        }
    }

    public function removeAll($userId) {
        $user = User::find($userId);
        $token = json_decode($user->google_access_token_json, true);

        $this->revokeToken($token);
        $user->provider_name = '';
        $user->remember_token = '';
        $user->google_access_token_json = '';
        $user->save();

        $this->removePictures($userId);
        $cacheFilename = $this->getCacheDirFilename($userId);
        if (file_exists($cacheFilename)) {
            unlink($cacheFilename);
        }
    }

    public function removePictures($userId){
        $dirPictures = $this->getUserDir($userId);
        if (file_exists($dirPictures)) {
            array_map('unlink', glob("$dirPictures/*.*"));
            rmdir($dirPictures);
        }
    }

    public function refreshInfos($userId) {
        $now = date("Y-m-d");
        $cacheFilename = $this->getCacheDirFilename($userId);
        $expired = false;
        if (file_exists($cacheFilename)) {
            $json = json_decode(file_get_contents($cacheFilename), true);
            //Rafraichit les photos chaque mois (necessite reconnexion)
            if (date('Y-m-d', strtotime($json["date"] . ' + 30 days')) < date('Y-m-d')){
                $expired = true;
                $this->removePictures($userId);

                //return redirect("/connect.php");
            }
        }

        if ($expired or !file_exists($cacheFilename)) {
            // Set up the Photos Library Client that interacts with the API
            $pictures = $this->getPictures();
            $events = $this->getEvents();
            $albums = $this->getAlbums();
            $json = ["pictures_cache"=> [], "pictures" => $pictures, "events" => $events, "albums" => $albums, "date"=>$now];
            file_put_contents($cacheFilename, json_encode($json));
        }
    }

    public function getAlbums() {
        $user = $this->getTheUser();
        $albums = [];
        try {
            $photosLibraryClient = $this->getPhotoLibraryClient($user->id);
            $albumsPhotos = $photosLibraryClient->listAlbums();

            foreach ($albumsPhotos as $album) {
                $albums[$album->getId()] = $album->getTitle();
            }
        }catch (\Exception $e){
            //Do nothing
        }
        return $albums;
    }

    public function getPictures() {
        $user = $this->getTheUser();
        $pictures = [];
        try {
            $photosLibraryClient = $this->getPhotoLibraryClient($user->id);

            $albumIdSelected = $user->album_id;
            if (empty($albumIdSelected)) {
                $response = $photosLibraryClient->searchMediaItems();
            } else {
                $response = $photosLibraryClient->searchMediaItems(['albumId' => $albumIdSelected]);
            }
            foreach ($response->iterateAllElements() as $picture) {
                $pictures[] = ["id" => $picture->getId()];
            }
        }catch (\Exception $e){
            //Do nothing
        }
        return $pictures;
    }

    public function readInfos($userId) {
        $now = date("Y-m-d");
        $photosLibraryClient = $this->getPhotoLibraryClient($userId);
        $cacheFilename = $this->getCacheDirFilename($userId);
        $json = json_decode(file_get_contents($cacheFilename), true);
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
                    $key = $event["date"]."-".$event["summary"];
                    $events[$key] = $event;
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
            $dirPictures = $this->getUserDir($userId);

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

        return ["albums" => $albums,"events" => $events,"photo" => $photo];
    }

    public function getTheUser() {
        $authEmail = Cookie::get("auth_email");
        $authPassword = Cookie::get("auth_password");
        return User::where("email",$authEmail)->where("password",$authPassword)->first();
    }

    public function downloadMedia($userId, $json, $cacheFilename) {
        $dirPictures = $this->getUserDir($userId);
        if (!file_exists($dirPictures)) {
            mkdir($dirPictures);
        }

        $error = '';
        $downloadMax = 5;
        $download = 0;
        $listPhotos = $json['pictures'];
        try {
            $photosLibraryClient = $this->getPhotoLibraryClient($userId);
            if (count($listPhotos) > 0) {
                foreach ($listPhotos as $photo) {
                    if ($download < $downloadMax) {
                        $mediaItem = $photosLibraryClient->getMediaItem($photo["id"]);
                        $urlPhoto = $mediaItem->getBaseUrl() . "=w500-h400";
                        $filename = $mediaItem->getFilename();
                        if (stripos($filename, ".mp4") === false) {
                            if (!file_exists($dirPictures . "/" . $filename)) {
                                file_put_contents($dirPictures . "/" . $filename, file_get_contents($urlPhoto));
                                $download++;
                            }
                        }
                    }
                }
            }
        }catch(\Exception $e) {
            $error = $e->getMessage();
        }

        $nbFiles = 0;
        $files = scandir($dirPictures);
        foreach ($files as $filename) {
            if ($filename != "." && $filename != "..") {
                $json["pictures_cache"][] = $filename;
                $nbFiles++;
            }
        }

        file_put_contents($cacheFilename, json_encode($json));
        return ['error'=>$error, 'nbFiles' =>$nbFiles, 'listPhotos'=>$listPhotos];
    }

    public function getEvents() {
        //Liste des evenements
        $user = $this->getTheUser();
        $events = [];
        try {
            $eventsTmp = Event::get(null, null, [], $user->email);

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

                    $events[] = ["date" => $date, "hour" => $hour, "frequency" => $reccurence,
                        "summary" => $item->__get("summary"), "css" => ""];

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
        }catch(\Exception $e){
            $events[] = ["frequency"=>"","day"=>"","css"=>"","date"=>date("Y-m-d"),"hour"=>"23:59","summary"=>"Le calendrier de ".$user->email." email n'a pas autorisé l'accès à homepage-v2@chrome-frame-692.iam.gserviceaccount.com"];
        }

        return $events;
    }

    public function getPhotoLibraryClient($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $clientSecretJson = json_decode(
                file_get_contents(storage_path(self::CLIENT_SECRET)),
                true
            )['web'];

            $clientId = $clientSecretJson['client_id'];
            $clientSecret = $clientSecretJson['client_secret'];

            $scopes = ['https://www.googleapis.com/auth/photoslibrary.readonly',
                'https://www.googleapis.com/auth/calendar.readonly',
                'https://www.googleapis.com/auth/userinfo.email'];

            $token = json_decode($user->google_access_token_json, true);
            $refresh_token = isset($token["refresh_token"]) ? $token["refresh_token"] : $token["access_token"];
            $authCredentials = new UserRefreshCredentials(
                $scopes,
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refresh_token
                ]
            );
            return new PhotosLibraryClient(['credentials' => $authCredentials]);
        }
    }

    public function getUserClient($userId){
        $user = User::find($userId);
        if ($user) {
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
    }

    /**
     * Gets a google client
     *
     * @return \Google_Client
     */
    public function getClient():\Google_Client
    {
        // load our config.json that contains our credentials for accessing google's api as a json string
        $configJson = storage_path(self::CLIENT_SECRET);

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

    public function getPicture($filename, $userId) {
        $dirPictures = $this->getUserDir($userId);
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

    public function updateUserToken($userId, $accessToken) {
        $user = User::find($userId);
        if (!isset($accessToken["error"])) {
            $user->provider_name ='google';
            $user->google_access_token_json = json_encode($accessToken);
            $user->save();
        }
    }

    public function getUserDir($userId) {
        $dirPictures = storage_path("app/pictures");
        if (!file_exists($dirPictures)) {
            mkdir($dirPictures);
        }
        $dirPictures = storage_path("app/pictures/".$this->getCacheFilename($userId));
        if (!file_exists($dirPictures)) {
            mkdir($dirPictures);
        }
        return $dirPictures;
    }

    public function getCacheDirFilename($userId) {
        return storage_path("app")."/".$this->getCacheFilename($userId).".json";
    }

    public function getCacheFilename($userId){
        $user = User::find($userId);
        if ($user) {
            return md5($user->email);
        } else {
            return "cache";
        }
    }
}
