<div class="panel-body body">
	<form id="nav">
		<div class="row">
			<div class=" col-md-13">
				<div class="panel-body" align="center">
					{$langs['labels']['Congratulation! Welcome to EspoCRM!']}
				</div>
			</div>
		</div>
	</form>
</div>

{if $cronHelp}
<div class="cron-help">
	&nbsp;{$cronTitle}
	<pre>
	{$cronHelp}
	</pre>
</div>
{/if}
<footer class="modal-footer">
	<button class="btn btn-primary" type="button" id="start">{$langs['labels']['Go to EspoCRM']}</button>
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