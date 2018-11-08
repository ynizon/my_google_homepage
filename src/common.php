<?php
/**
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require '../vendor/autoload.php';

use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Auth\OAuth2;

session_start();

$templates = new League\Plates\Engine('views');
$templates->addFolder('views', 'views');

/**
 * If there are no credentials, renders the $template page.
 * @param string $connectTemplate The page to render if there are no credentials, which should allow
 * the user to connect again.
 */
function checkCredentials($connectTemplate)
{
	
	if (isset($_COOKIE["expires_at"]) and isset($_COOKIE["auth_token"])){
		//if ($_COOKIE["expires_at"]>time() or $_COOKIE["expires_at"] == ""){
			//Refresh le token			
			$tab = unserialize($_COOKIE["auth_token"]);
			$oauth2 = initoAuth("@",$tab["access_token"],$tab["refresh_token"]);						
		//}
	}else{
		if (!isset($_SESSION['credentials'])) {
			echo $connectTemplate;
			exit;
			
		}
	}
}
//echo var_dump($_COOKIE);
//Initialisation du client oAuth
function initoAuth($sCode, $sAccessToken, $sRefreshToken){	
	$scopes = ['https://www.googleapis.com/auth/photoslibrary.readonly'];
    
	$ini = parse_ini_file('../config.ini');
	$redirectURI = $ini['google_authentication_redirect_url'];
	
	$clientSecretJson = json_decode(
        file_get_contents('../client_secret.json'),
        true
    )['web'];
    $clientId = $clientSecretJson['client_id'];
    $clientSecret = $clientSecretJson['client_secret'];

	
    $oauth2 = new OAuth2([
        'clientId' => $clientId,
        'clientSecret' => $clientSecret,
        'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
        // Where to return the user to if they accept your request to access their account.
        // You must authorize this URI in the Google API Console.
        'redirectUri' => $redirectURI,
        'tokenCredentialUri' => 'https://www.googleapis.com/oauth2/v4/token',
        'scope' => $scopes,
		'access_type' => 'offline',
		'prompt'=>'consent'
    ]);
	
	if ($sAccessToken != ""){
		$oauth2->setAccessToken($sAccessToken);
	}
	if ($sRefreshToken != ""){
		$oauth2->setRefreshToken($sRefreshToken);
	}
	
	if ($sCode != "" and $sCode != "@"){
		$oauth2->setCode($sCode);
		setcookie('code', $_GET['code'], time() + 365*24*3600, null, null, false, true);	
	}
	
	if ($sCode != ""){
		$authToken = $oauth2->fetchAuthToken();

		$refreshToken = $authToken['access_token'];
		$expires_in = $authToken['expires_in'];
		
		// The UserRefreshCredentials will use the refresh token to 'refresh' the credentials when
		// they expire.
		$_SESSION['credentials'] = new UserRefreshCredentials(
			$scopes,
			[
				'client_id' => $clientId,
				'client_secret' => $clientSecret,
				'refresh_token' => $refreshToken
			]
		);
		
		
		//On enregistre les cookies en memoire pour le refresh token
		setcookie('credentials', serialize($_SESSION['credentials']), time() + 365*24*3600, null, null, false, true);			
		setcookie('expires_at', time()+3600, time() + 365*24*3600, null, null, false, true);	
		
		if ($sCode != "@"){
			setcookie('auth_token', serialize($authToken), time() + 365*24*3600, null, null, false, true);		
		}		
	}
	
	$refreshToken = $authToken['access_token'];
	
	return $oauth2;
}

/**
 * Requests access to the user's Google Photos account, and stores the resulting
 * {@link UserRefreshCredentials} object in the current session, with the key 'credentials'.
 *
 * This function handles the cases before and after the user has been redirected to grant access.
 * The calls are identical, but you must ensure that the $scopes and $redirectURI are consistent.
 *
 * When the user has successfully connected, they will be redirected to the index page.
 *
 * You should request the most restrictive scope that you can, for you user case. See
 * {@link https://developers.google.com/photos/library/guides/authentication-authorization} for more
 * details.
 *
 * The $redirectURI must be authorized for the given client secret in the Google API Console.
 *
 * You should store the refresh token for each user. See
 * {@link https://developers.google.com/identity/protocols/OAuth2WebServer} for more details.
 *
 */
function connectWithGooglePhotos()
{
	$scopes = ['https://www.googleapis.com/auth/photoslibrary.readonly'];    
	
    // The authorization URI will, upon redirecting, return a parameter called code.
    if (!isset($_GET['code'])) {
		$oauth2 = initoAuth("","","");
        $authenticationUrl = $oauth2->buildFullAuthorizationUri(['access_type' => 'offline', 'prompt'=>'consent']);
        header("Location: " . $authenticationUrl);
    } else {		
		if (isset($_GET["code"])){
			// With the code returned by the OAuth flow, we can retrieve the refresh token.
			$oauth2 = initoAuth($_GET['code'],"","");			
		}
		
        // Return the user to the home page.
        header("Location: index.php");
    }
}
