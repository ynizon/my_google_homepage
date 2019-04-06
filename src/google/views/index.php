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
			<img src='<?php echo $picture["url"];?>'>
			<br/><br/>
			Définir cette page d'accueil par défaut pour 
			<a target="_blank" href='https://support.google.com/chrome/answer/95314?hl=fr'></a>
			<a href='https://chrome.google.com/webstore/detail/new-tab-redirect/icpgjfneehieebagbmdbhnlpiopdcmna' target="_blank">Chrome</a>, 
			<a target="_blank" href='https://support.mozilla.org/fr/kb/comment-definir-page-accueil'>Firefox</a>
			<?php
			/*
			$ini = parse_ini_file('../../photoslibrary-sample.ini');
			?>
			<input style="padding:5px;cursor:pointer" TYPE="button" VALUE="Faire de cette page, ma page par défaut" onClick="this.style.behavior='url(#default#homepage)'; this.setHomePage('<?php echo str_replace("connect","index",$ini['google_authentication_redirect_url']); ?>');">
			<?php
			*/
			?>
			<script>
				document.getElementById("q").focus();
			</script>
		</form>
		<?php
		/*
		<h3>Avancé</h3>
        <?php if (isset($_GET['media-type'])
              || isset($_GET['included-categories'])
              || isset($_GET['excluded-categories'])
              || isset($_GET['start-date'])
              || isset($_GET['end-date'])) : ?>
            <p>You're viewing photos in the user's library that match your search filters.</p>
        <?php else :?>
            <p>You're viewing all photos in the user's library. Use the search action to refine your
              results with filters.</p>
        <?php endif ?>
        <a href="filters.php" class="mdl-button mdl-button--raised mdl-button--colored">Search</a>
		*/
		?>
    </div>
</div>
