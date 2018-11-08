<?php $this->layout('template', ['title' => date("d/m/Y",$picture["timestamp"]). "  --  ".$picture["filename"]]); ?>

<div class="mdl-grid">
    <div class="mdl-cell mdl-cell--12-col">
        <form method="get" action="https://www.google.fr/search" style="margin:auto;text-align:center">
			<br/><br/>
			<img src="https://www.google.fr/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png" style="width:300px"/>
			<br/><br/><br/>
			<input type="text" name="q" id="q" value="" style="padding:5px;width:200px" />&nbsp;&nbsp;
			<input type="submit" name="Rechercher"  style="cursor:pointer;padding:5px" value="Rechercher" />
			<br/><br/>
			<a href='https://photos.google.com/search/<?php echo $picture["filename"];?>'><img src='<?php echo $picture["url"];?>'></a>
			<br/><br/>
			<?php
			if (count($albums)>0){
			?>
				Choisir uniquement des photos dans
				<script>
				function changeAlbum(){
					window.location.href="?album_id="+document.getElementById("album_id").value;
				}
				</script>
				<select name="album_id" id="album_id" onchange="changeAlbum()">
					<option value="-">toutes les photos</option>
					<?php
					foreach ($albums as $album_id=>$album){
						?>
						<option <?php if($album_id == $album_id_sel){echo "selected";} ?> value="<?php echo $album_id;?>"><?php echo $album;?></option>
						<?php
					}
					?>
				</select><br/>
			<?php
			}
			?>
			Définir cette page d'accueil par défaut pour 
			<a target="_blank" href='https://support.google.com/chrome/answer/95314?hl=fr'></a>
			<a href='https://chrome.google.com/webstore/detail/new-tab-redirect/icpgjfneehieebagbmdbhnlpiopdcmna' target="_blank">Chrome</a>, 
			<a target="_blank" href='https://support.mozilla.org/fr/kb/comment-definir-page-accueil'>Firefox</a>
			<br/>
			<a href='/privacy.php'>Politique de confidentialité</a>
			&nbsp;<a href='/tos.php'>Conditions de service</a>
			<script>
				document.getElementById("q").focus();
			</script>
		</form>		
    </div>
</div>
