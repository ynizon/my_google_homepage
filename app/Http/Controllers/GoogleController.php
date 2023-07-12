<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller as Controller;

use App\Models\User;

/**
 * Google Controller
 *
 */
class GoogleController extends Controller
{
    /**
     * Return the url of the google auth.
     * FE should call this and then direct to this url.
     *
     * @return JsonResponse
     */
    public function getAuthUrl(Request $request):JsonResponse
    {
        /**
         * Create google client
         */
        $client = $this->getClient();

        /**
         * Generate the url at google we redirect to
         */
        $authUrl = $client->createAuthUrl();

        /**
         * HTTP 200
         */
        return response()->json($authUrl, 200);
    } // getAuthUrl


    /**
     * Login and register
     * Gets registration data by calling google Oauth2 service
     *
     * @return JsonResponse
     */
    public function postLogin(Request $request):JsonResponse
    {

        /**
         * Get authcode from the query string
         */
        $authCode = urldecode($request->input('auth_code'));

        /**
         * Google client
         */
        $client = $this->getClient();

        /**
         * Exchange auth code for access token
         * Note: if we set 'access type' to 'force' and our access is 'offline', we get a refresh token. we want that.
         */
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        /**
         * Set the access token with google. nb json
         */
        $client->setAccessToken(json_encode($accessToken));

        /**
         * Get user's data from google
         */
        $service = new \Google\Service\Oauth2($client);
        $userFromGoogle = $service->userinfo->get();

        /**
         * Select user if already exists
         */
        $user = User::where('provider_name', '=', 'google')
            ->where('provider_id', '=', $userFromGoogle->id)
            ->first();

        /**
         */
        if (!$user) {
            $user = User::create([
                'provider_id' => $userFromGoogle->id,
                'provider_name' => 'google',
                'google_access_token_json' => json_encode($accessToken),
                'name' => $userFromGoogle->name,
                'email' => $userFromGoogle->email,
                'role_id' => 2,
                //'avatar' => $providerUser->picture, // in case you have an avatar and want to use google's
            ]);
        }
        /**
         * Save new access token for existing user
         */
        else {
            $user->google_access_token_json = json_encode($accessToken);
            $user->save();
        }

        /**
         * Log in and return token
         * HTTP 201
         */
        $token = $user->createToken("Google")->accessToken;
        return response()->json($token, 201);
    } // postLogin


    /**
     * Get meta data on a page of files in user's google drive
     *
     * @return JsonResponse
     */
    public function getDrive(Request $request):JsonResponse
    {
        /**
         * Get google api client for session user
         */
        $client = $this->getUserClient();

        /**
         * Create a service using the client
         * @see vendor/google/apiclient-services/src/
         */
        $service = new \Google\Service\Drive($client);

        /**
         * The arguments that we pass to the google api call
         */
        $parameters = [
            'pageSize' => 10,
        ];

        /**
         * Call google api to get a list of files in the drive
         */
        $results = $service->files->listFiles($parameters);

        /**
         * HTTP 200
         */
        return response()->json($results, 200);
    }


    /**
     * Gets a google client
     *
     * @return \Google_Client
     */
    private function getClient():\Google_Client
    {
        // load our config.json that contains our credentials for accessing google's api as a json string
        $configJson = base_path().'/config.json';

        // define an application name
        $applicationName = 'myfancyapp';

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
                \Google\Service\Drive::DRIVE_METADATA_READONLY
            ]
        );
        $client->setIncludeGrantedScopes(true);
        return $client;
    } // getClient


    /**
     * Returns a google client that is logged into the current user
     *
     * @return \Google_Client
     */
    private function getUserClient():\Google_Client
    {
        /**
         * Get Logged in user
         */
        $user = User::where('id', '=', auth()->guard('api')->user()->id)->first();

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
    } // getUserClient
}
