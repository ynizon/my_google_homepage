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

<div id="" style="margin:auto;text-align:center">
	 <form method="get" action="https://www.google.fr/search" >
		<br/><br/>
		<img src="images/googlelogo_color_272x92dp.png" style="width:300px"/>
		<br/><br/><br/>
		<input type="text" name="q" id="q" value="" style="padding:5px;width:200px" />&nbsp;&nbsp;
		<input type="submit" name="Rechercher"  style="cursor:pointer;padding:5px" value="Rechercher" />
		<br/><br/>
		<img src='images/screenshot.jpg'>
		<script>
			document.getElementById("q").focus();
		</script>
		
		<br/>
		<a class="connect-btn" href="connect.php">
			<img src="images/google_photos_logo.png">
			Alors connectez-vous à Google Photos
		</a>		
		<br/>
		<div style="padding-top:70px">
			<a href='/privacy.php'>Politique de confidentialité</a>
			&nbsp;<a href='/tos.php'>Conditions de service</a>
		</div>
	</form>	
	
</div>