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

use Google\ApiCore\ApiException;
use Google\Photos\Library\V1\FiltersBuilder;
use Google\Photos\Library\V1\PhotosLibraryClient;


require '../common/common.php';

/**
* Removes the user's credentials for Photos Library API access.
*/
if (isset($_GET['clear'])) {
    unset($_SESSION['credentials']);
	foreach (array("expires_in","auth_token","refresh_date","expires_at") as $sCookie){
		if (isset($_COOKIE[$sCookie])){
			setcookie($sCookie, "", 1);	
		}
	}	
}

/**
* Constructs a SearchMediaItems query from the form submission. If no query is set, then an
* unfiltered search is performed.
*/
$filtersBuilder = new FiltersBuilder();

$filtersBuilder ->setMediaTypeFromString("PHOTO");

$filtersBuilder->setIncludeArchivedMedia(isset($_GET['archived-media']));

if (isset($_GET['included-categories'])) {
    foreach ($_GET['included-categories'] as $includedCategory) {
        // These category strings are from the array PhotosLibraryClient::contentCategories().
        $filtersBuilder->addIncludedCategoryFromString($includedCategory);
    }
}

if (isset($_GET['excluded-categories'])) {
    foreach ($_GET['excluded-categories'] as $excludedCategory) {
        // These category strings are from the array PhotosLibraryClient::contentCategories().
        $filtersBuilder->addExcludedCategoryFromString($excludedCategory);
    }
}

if (isset($_GET['start-date']) && $_GET['start-date'] != '') {
    $startDate = new DateTime($_GET['start-date']);

    if (isset($_GET['end-date']) && $_GET['end-date'] != '') {
        $endDate = new DateTime($_GET['end-date']);
        $filtersBuilder->addDateRangeFromDateTime($startDate, $endDate);
    } else {
        $filtersBuilder->addDateFromDateTime($startDate);
    }
}

/**
 * Sends the request, as constructed above, to the Photos Library API, and renders the response.
 */
$templates->addFolder('google', '../google/views');
 
checkCredentials($templates->render('google::connect'));
//echo var_dump(($_COOKIE['credentials']));exit();
//$photosLibraryClient = new PhotosLibraryClient(['credentials' => json_decode($_COOKIE['credentials'],true)]);
$photosLibraryClient = new PhotosLibraryClient(['credentials' => $_SESSION['credentials']]);

try {	
	//Suppression des anciens fichiers
	$files = scandir("../tmp/");
	foreach ($files as $file){
		if ($file != ".." and $file != "."){
			if (date("Y-m")>$file){
				unlink($file);
			}
		}
	}

    // Many accounts have too many media items to display on a single web page. Instead, we get a
    // single page of results. You can retrieve the page token from the $pagedResponse to populate
    // later pages.
	$tabInfo = array("items"=>array(),"token"=>"", "date"=>mktime(0,0,0,1,1,1970));

	$uniqid = uniqid();
	if (isset($_COOKIE["uniqid"])){
		$uniqid = $_COOKIE["uniqid"];
	}
		
	$file = "../tmp/".date("Y-m")."-files".$uniqid.".json";
	if (file_exists($file)){
		$tabInfo = json_decode(file_get_contents($file),true);
	}
	
	//ON rafraichit 1 fois par heure	
	$delta = (time()-$tabInfo["date"]);
	
	//On refresh une fois par mois
	$bRefresh = true;
	if (isset($_COOKIE["refresh_date"])){
		if ($_COOKIE["refresh_date"] == date("Y-m")){
			$bRefresh = false;
		}
	}
	if ($bRefresh){
		$tabInfo = array("items"=>array(),"token"=>"", "date"=>mktime(0,0,0,1,1,1970));
		$tabInfo["date"] = time();
		do{
			$pagedResponse = $photosLibraryClient->searchMediaItems(
				['filters' => $filtersBuilder->build(), 'pageToken'=>$tabInfo["token"]]
			);
			
			foreach ($pagedResponse->getPage()->getIterator() as $iterator){
				//$timestamp = $iterator->getMediaMetadata()->getCreationTime()->getSeconds();
				$tabInfo["items"][$iterator->getId()] = $iterator->getId();//array("timestamp"=>$timestamp,"url"=>$iterator->getBaseUrl(),"filename"=>$iterator->getFilename());
			}
			
			$nextToken = $pagedResponse->getPage()->getNextPageToken();
			if ($nextToken != ""){
				$tabInfo["token"] = $nextToken;
			}
		}while($nextToken != "");
		
		//Creation du fichier en sortie
		file_put_contents($file,json_encode($tabInfo));
		setCookie("uniqid", $uniqid);
		setcookie('refresh_date', date("Y-m"), time() + 365*24*3600, null, null, false, true);	
	}
	
	shuffle($tabInfo["items"]);
	$picture_id = array_shift($tabInfo["items"]);	
	$oPicture = $photosLibraryClient->GetMediaItem($picture_id);

	$timestamp = $oPicture->getMediaMetadata()->getCreationTime()->getSeconds();	
	$picture = array("timestamp" => $timestamp,
					 "filename"=>$oPicture->getFilename(),
					 "url"=>$oPicture->getBaseUrl());
	
		
	echo $templates->render(
        'google::index',
        ['picture' => $picture]
    );	
	
} catch (ApiException $e) {
    // If the API throws an error, render it. You can induce this by, for example, setting the page
    // size to be greater than the maximum. The exceptions are not user-friendly, this is just for
    // demonstrative purposes.
	header("location:connect.php");
    echo $templates->render('error', ['exception' => $e]);
}
