<div style="background-color: #4A6492; padding: 3px 10px;" class="panel-heading">
	{if $isBuilt eq true}
		<img src="../client/img/logo.png">
	{else}
		<img src="../frontend/client/img/logo.png">
	{/if}
</div>
<header class="panel-heading">
	<div class="row">
		<div class="col-md-10">
			<h4 class="panel-title">
				{$langs['labels']["{$action} page title"]}
			</h4>
		</div>
		<div class="col-md-2 version" align="right">
			{$langs['labels']['Version']} {$version}
		</div>
	</div>
</header>