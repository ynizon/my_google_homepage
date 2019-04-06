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
checkCredentials($templates->render('filters::connect'));
$photosLibraryClient = new PhotosLibraryClient(['credentials' => $_SESSION['credentials']]);

try {	
    // Many accounts have too many media items to display on a single web page. Instead, we get a
    // single page of results. You can retrieve the page token from the $pagedResponse to populate
    // later pages.
	$tabInfo = array("items"=>array(),"token"=>"", "date"=>"1970-01-01");

	$uniqid = uniqid();
	if (isset($_COOKIE["uniqid"])){
		$uniqid = $_COOKIE["uniqid"];
	}
	
	$file = "files".$uniqid.".json";
	if (file_exists($file)){
		$tabInfo = json_decode(file_get_contents($file),true);
	}
	
	//ON rafraichit 1 fois par jour
	if ($tabInfo["date"]!=date("Y-m-d")){
		$tabInfo["date"] = date("Y-m-d");
		do{			
			$pagedResponse = $photosLibraryClient->searchMediaItems(
				['filters' => $filtersBuilder->build(), 'pageToken'=>$tabInfo["token"]]
			);
			
			foreach ($pagedResponse->getPage()->getIterator() as $iterator){
				$tabInfo["items"][$iterator->getBaseUrl()] = $iterator->getBaseUrl();
			}
			
			$nextToken = $pagedResponse->getPage()->getNextPageToken();
			if ($nextToken != ""){
				$tabInfo["token"] = $nextToken;
			}
		}while($nextToken != "");
		
		//Creation du fichier en sortie
		file_put_contents($file,json_encode($tabInfo));
		setCookie("uniqid", $uniqid);
	}
	
	shuffle($tabInfo["items"]);
	$picture = array_shift($tabInfo["items"]);
	//echo $picture;
	?>
	<html>
		<body style="margin:auto;text-align:center;">
			<form method="get" action="https://www.google.fr" >
				<br/><br/>
				<img src="https://www.google.fr/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png" style="width:300px"/>
				<br/><br/><br/>
				<input type="text" name="q" id="q" value="" />
				<input type="submit" name="Rechercher" value="Rechercher" />
				<br/><br/>
				<img src='<?php echo $picture;?>'>
				<script>
					document.getElementById("q").focus();
				</script>
			</form>
		</body>
	</html>
	<?php
	
} catch (ApiException $e) {
    // If the API throws an error, render it. You can induce this by, for example, setting the page
    // size to be greater than the maximum. The exceptions are not user-friendly, this is just for
    // demonstrative purposes.
    echo $templates->render('error', ['exception' => $e]);
}
