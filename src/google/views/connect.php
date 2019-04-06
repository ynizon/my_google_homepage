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

$this->layout('template', ['title' => 'Vous aussi vous aimeriez cette page de recherche avec vos photos ?'])
?>

<div id="">
	 <form method="get" action="https://www.google.fr/search" style="margin:auto;text-align:center">
		<br/><br/>
		<img src="https://www.google.fr/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png" style="width:300px"/>
		<br/><br/><br/>
		<input type="text" name="q" id="q" value="" style="padding:5px;width:200px" />&nbsp;&nbsp;
		<input type="submit" name="Rechercher"  style="cursor:pointer;padding:5px" value="Rechercher" />
		<br/><br/>
		<img src='screenshot.jpg'>
		<script>
			document.getElementById("q").focus();
		</script>
		
		<br/><br/>
		<a class="connect-btn" href="./connect.php">
			<img src="../common/google_photos_logo.png">
			Alors connectez-vous à Google Photos
		</a>
	</form>	
</div>