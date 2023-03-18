<div id="nav">
	<div id="nav-inner">
		<div id="logo-wrapper">
			<div id="logo"><a href="/">CE-PM</a></div>
		</div>
		<details open>
			<summary>TI-OS Packages</summary>
			<div class="subwrapper">
				<div class="navitem <?php if(($_GET['target'] == 'vpm') && ($_GET['cat'] == 'general')) {echo 'active';} ?>"><a href="?target=vpm&cat=general">General</a></div>
				<div class="navitem <?php if(($_GET['target'] == 'vpm') && ($_GET['cat'] == 'utilities')) {echo 'active';} ?>"><a href="?target=vpm&cat=utilities">Utilities</a></div>
				<div class="navitem <?php if(($_GET['target'] == 'vpm') && ($_GET['cat'] == 'games')) {echo 'active';} ?>"><a href="?target=vpm&cat=games">Games</a></div>
				<div class="navitem <?php if(($_GET['target'] == 'vpm') && ($_GET['cat'] == 'libraries')) {echo 'active';} ?>"><a href="?target=vpm&cat=libraries">Libraries</a></div>
			</div>
		</details>
		<details open>
			<summary>BOS Packages</summary>
			<div class="subwrapper">
				<div class="navitem <?php if(($_GET['target'] == 'bpm') && ($_GET['cat'] == 'general')) {echo 'active';} ?>"><a href="?target=bpm&cat=general">General</a></div>
				<div class="navitem <?php if(($_GET['target'] == 'bpm') && ($_GET['cat'] == 'utilities')) {echo 'active';} ?>"><a href="?target=bpm&cat=utilities">Utilities</a></div>
				<div class="navitem <?php if(($_GET['target'] == 'bpm') && ($_GET['cat'] == 'games')) {echo 'active';} ?>"><a href="?target=bpm&cat=games">Games</a></div>
				<div class="navitem <?php if(($_GET['target'] == 'bpm') && ($_GET['cat'] == 'libraries')) {echo 'active';} ?>"><a href="?target=bpm&cat=libraries">Libraries</a></div>
				<div class="navitem <?php if(($_GET['target'] == 'bpm') && ($_GET['cat'] == 'themes')) {echo 'active';} ?>"><a href="?target=bpm&cat=themes">Themes</a></div>
				<div class="navitem <?php if(($_GET['target'] == 'bpm') && ($_GET['cat'] == 'extensions')) {echo 'active';} ?>"><a href="?target=bpm&cat=extensions">Extensions</a></div>
			</div>
		</details>
		<div class="navitem major <?php if($_SERVER['PHP_SELF'] == 'downloads.php') {echo 'active';} ?>">Downloads</div>
		<div class="navitem major <?php if($_SERVER['PHP_SELF'] == 'docs.php') {echo 'active';} ?>">Documentation</div>
		<div class="spacer"></div>
        <div class="navitem">
            <ins class="adsbygoogle" style="display:inline-block;width:180px;height:180px" data-ad-client="ca-pub-6268292462233201" data-ad-slot="2284182095"></ins>
            <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
        </div>
        <div class="spacer"></div>
		<div id="login" class="navitem major <?php if($_SERVER['PHP_SELF'] == 'admin/') {echo 'active';} ?>"><a href="admin/">Pkg Admin</a></div>
	</div>
</div>
