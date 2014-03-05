<header class="panel-heading">
	<h4 class="panel-title">{$langs['Step3 page title']}</h4>
</header>
<div class="panel-body body">
	<div id="msg-box" class="alert hide"></div>
	<div class="loading-icon hide"></div>
	<form id="nav">							
		<div class="row">
			
			<div class=" col-md-6">
				<div class="row">
					<div class="cell cell-website col-sm-12 form-group">
						<label class="field-label-website control-label">{$langs['User Name']} *</label>
						<div class="field field-website">
							<input type="text" value="{$fields['user-name'].value}" name="user-name" class="main-element form-control">
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="cell cell-website col-sm-12 form-group">
						<label class="field-label-website control-label">{$langs['Password']} *</label>
						<div class="field field-website">
							<input type="password" value="{$fields['user-pass'].value}" name="user-pass" class="main-element form-control">
						</div>
					</div>
				</div>
					
				<div class="row">
					<div class="cell cell-website col-sm-12 form-group">
						<label class="field-label-website control-label">{$langs['Confirm Password']} *</label>
						<div class="field field-website">
							<input type="password" value="{$fields['user-confirm-pass'].value}" name="user-confirm-pass" class="main-element form-control">
						</div>
					</div>
				</div>
			</div>
			
		</div>
	</form>				
</div>
<footer class="modal-footer">
	<button class="btn btn-default" type="button" id="back">{$langs['Back']}</button>
	<button class="btn btn-primary" type="button" id="next">{$langs['Next']}</button>
</footer>
<script>
	{literal}
	$(function(){
	{/literal}
		var langs = {$langsJs};
		var ajaxUrls = {$ajaxUrls};
	{literal}
		var installScript = new InstallScript({action: 'step3', langs: langs, ajaxUrls: ajaxUrls});
	})
	{/literal}
</script>