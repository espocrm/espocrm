<form id="nav">
	<div class="panel-body">
		<div id="msg-box" class="alert hide"></div>
		<div class="row">
			<div class=" col-md-13">
				<div align="center">
				{$langs['Main page header']}
				</div>
			</div>
		</div>
	</div>
	<footer class="modal-footer">
		<div class="cell cell-website pull-left" align="left">
			<label class="field-label-website control-label">{$langs['Choose your language:']}</label>
			<div class="field field-website">
				<select name="user-lang" class="form-control">
					{foreach from=$langs['user languages'] item=lbl key=val}
						{if $val == $fields['user-lang'].value}
							<option selected="selected" value="{$val}">{$lbl}</option>
						{else}
							<option value="{$val}">{$lbl}</option>
						{/if}
					{/foreach}
				</select>
			</div>
		</div>
		<button class="btn btn-primary" type="button" id="start">{$langs['Start']}</button>
	</footer>
</form>
<script>
	{literal}
	$(function(){
	{/literal}
		var langs = {$langsJs};
	{literal}
		var installScript = new InstallScript({action: 'main', langs: langs});
	})
	{/literal}
</script>