<header class="panel-heading">
	<h4 class="panel-title">{$langs['Finish page title']}</h4>
</header>
<div class="panel-body body">
	<form id="nav">
		<div class="row">
			<div class=" col-md-13">
				<div class="panel-body" align="center">
					{$langs['Congratulation! Welcome to EspoCRM!']}
				</div>
			</div>
		</div>
	</form>
</div>
<footer class="modal-footer">
	<button class="btn btn-primary" type="button" id="start">{$langs['Go to EspoCRM']}</button>
</footer>
<script>
	{literal}
	$(function(){
	{/literal}
		var langs = {$langsJs};
	{literal}
		var installScript = new InstallScript({action: 'finish', langs: langs});
	})
	{/literal}
</script>