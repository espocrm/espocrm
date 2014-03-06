<header class="panel-heading">
	<h4 class="panel-title main-title">{$langs['Step4 page title']}</h4>
</header>
<div class="panel-body body">
	<div id="msg-box" class="alert hide"></div>
	<div class="loading-icon hide"></div>
	<form id="nav">
		<div class="row">
			<div class=" col-md-8" style="width:100%" >
				<div class="record">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title subpanel" >{$langs['Locale']}</h4>
						</div>
						<div class="panel-body">
							<div class="row">
								<div class="cell cell-dateFormat  col-sm-6  form-group">
									<label class="field-label-dateFormat control-label">
										{$langs['Date Format']}
									</label>
									<div class="field field-dateFormat">
										<select name="dateFormat" class="form-control main-element">
											{foreach from=$settingsDefaults['dateFormat'].options item=lbl key=val}
												{if $val == $fields['dateFormat'].value}
													<option selected="selected" value="{$val}">{$lbl}</option>
												{else}
													<option value="{$val}">{$lbl}</option>
												{/if}
											{/foreach}
											</select>
										</div>
									</div>
				
									<div class="cell cell-timeFormat  col-sm-6  form-group">
										<label class="field-label-timeFormat control-label">
											{$langs['Time Format']}
										</label>
										<div class="field field-timeFormat">
											<select name="timeFormat" class="form-control main-element">
												{foreach from=$settingsDefaults['timeFormat'].options item=lbl key=val}
													{if $val == $fields['timeFormat'].value}
														<option selected="selected" value="{$val}">{$lbl}</option>
													{else}
														<option value="{$val}">{$lbl}</option>
													{/if}
												{/foreach}
											</select>
										</div>
									</div>

								</div>			
				
								<div class="row">
									<div class="cell cell-timeZone  col-sm-6  form-group">
										<label class="field-label-timeZone control-label">
											{$langs['Time Zone']}
										</label>
										<div class="field field-timeZone">
											<select name="timeZone" class="form-control main-element"> 
												{foreach from=$settingsDefaults['timeZone'].options item=lbl key=val}
													{if $val == $fields['timeZone'].value}
														<option selected="selected" value="{$val}">{$lbl}</option>
													{else}
														<option value="{$val}">{$lbl}</option>
													{/if}
												{/foreach}
											</select>
										</div>
									</div>
								
									<div class="cell cell-weekStart  col-sm-6  form-group">
										<label class="field-label-weekStart control-label">
											{$langs['First Day of Week']}
										</label>
										<div class="field field-weekStart">
											<select name="weekStart" class="form-control main-element"> 
												{foreach from=$settingsDefaults['weekStart'].options item=lbl key=val}
													{if $val == $fields['weekStart'].value}
														<option selected="selected" value="{$val}">{$lbl}</option>
													{else}
														<option value="{$val}">{$lbl}</option>
													{/if}
												{/foreach}
											</select>
										</div>
									</div>
			
								</div>			
				
								<div class="row">
				
									<div class="cell cell-defaultCurrency  col-sm-6  form-group">
										<label class="field-label-defaultCurrency control-label">
											{$langs['Default Currency']}
										</label>
										<div class="field field-defaultCurrency">
											<select name="defaultCurrency" class="form-control main-element"> 
												{foreach from=$settingsDefaults['defaultCurrency'].options item=lbl key=val}
													{if $val == $fields['defaultCurrency'].value}
														<option selected="selected" value="{$val}">{$lbl}</option>
													{else}
														<option value="{$val}">{$lbl}</option>
													{/if}
												{/foreach}
											</select>
										</div>
									</div>
								</div>			
				
								<div class="row">
				
									<div class="cell cell-thousandSeparator  col-sm-6  form-group">
										<label class="field-label-thousandSeparator control-label">
											{$langs['Thousand Separator']} *
										</label>
										<div class="field field-thousandSeparator">
											<input type="text" class="main-element form-control" name="thousandSeparator" value="{$fields['thousandSeparator'].value}">
										</div>
									</div>
				
									<div class="cell cell-decimalMark  col-sm-6  form-group">
										<label class="field-label-decimalMark control-label">
											{$langs['Decimal Mark']} *</label>
										<div class="field field-decimalMark">
											<input type="text" class="main-element form-control" name="decimalMark" value="{$fields['decimalMark'].value}">

										</div>
									</div>
								</div>			
				
								<div class="row">
				
									<div class="cell cell-language  col-sm-6  form-group">
										<label class="field-label-language control-label">
											{$langs['Language']}
										</label>
										<div class="field field-language">
											<select name="language" class="form-control main-element"> 
												{foreach from=$settingsDefaults['language'].options item=lbl key=val}
													{if $val == $fields['language'].value}
														<option selected="selected" value="{$val}">{$lbl}</option>
													{else}
														<option value="{$val}">{$lbl}</option>
													{/if}
												{/foreach}
											</select>
										</div>
									</div>
								</div>			
							</div>
						</div>
						<div class="panel panel-default">
		
						<div class="panel-heading"><h4 class="panel-title subpanel">{$langs['Outbound Email Configuration']}</h4></div>
		
						<div class="panel-body">
							<div class="row">
								<div class="cell cell-outboundEmailFromName  col-sm-6  form-group">
									<label class="field-label-outboundEmailFromName control-label">
										{$langs['From Name']}</label>
									<div class="field field-outboundEmailFromName">
						
										<input type="text" class="main-element form-control" name="outboundEmailFromName" value="{$fields['outboundEmailFromName'].value}">


									</div>
								</div>
				
								<div class="cell cell-outboundEmailFromAddress  col-sm-6  form-group">
									<label class="field-label-outboundEmailFromAddress control-label">
										{$langs['From Address']}</label>
									<div class="field field-outboundEmailFromAddress">
						
									<input type="text" class="main-element form-control" name="outboundEmailFromAddress" value="{$fields['outboundEmailFromAddress'].value}">

								</div>
							</div>
						</div>			
				
					<div class="row">
						<div class="cell cell-outboundEmailIsShared  col-sm-6  form-group">
							<label class="field-label-outboundEmailIsShared control-label">
								{$langs['Is Shared']}
							</label>
						<div class="field field-outboundEmailIsShared">
						<input type="checkbox" {if $fields['outboundEmailIsShared'].value} checked {/if} name="outboundEmailIsShared" class="main-element">

						</div>
					</div>
				</div>
</div>
</div>
						<div class="panel panel-default">
							<div class="panel-heading"><h4 class="panel-title subpanel">{$langs['SMTP']}</h4></div>
							<div class="panel-body">
				
								<div class="row">
				
									<div class="cell cell-smtpServer  col-sm-6  form-group">
										<label class="field-label-smtpServer control-label">
											{$langs['smtpServer']}
										</label>
										<div class="field field-smtpServer">
											<input type="text" class="main-element form-control" name="smtpServer" value="{$fields['smtpServer'].value}">

										</div>
									</div>
									<div class="cell cell-smtpPort  col-sm-6  form-group">
										<label class="field-label-smtpPort control-label">
											{$langs['smtpPort']}
										</label>
									<div class="field field-smtpPort">
										<input type="text" class="main-element form-control" name="smtpPort" value="{$fields['smtpPort'].value}" pattern="[\-]?[0-9]*" maxlength="4">
									</div>
								</div>
				
							</div>			
				
							<div class="row">
								<div class="cell cell-smtpAuth  col-sm-6  form-group">
									<label class="field-label-smtpAuth control-label">
										{$langs['smtpAuth']}
									</label>
									<div class="field field-smtpAuth">
										<input type="checkbox" name="smtpAuth" class="main-element" {if $fields['smtpAuth'].value} checked {/if}>
									</div>
								</div>
								<div class="cell cell-smtpSecurity  col-sm-6  form-group">
									<label class="field-label-smtpSecurity control-label">
										{$langs['smtpSecurity']}
									</label>
									<div class="field field-smtpSecurity">
										<select name="smtpSecurity" class="form-control main-element"> 
											{foreach from=$settingsDefaults['smtpSecurity'].options item=lbl key=val}
												{if $val == $fields['smtpSecurity'].value}
													<option selected="selected" value="{$val}">{$lbl}</option>
												{else}
													<option value="{$val}">{$lbl}</option>
												{/if}
											{/foreach}
										</select>
									</div>
								</div>
				
							</div>			
				
							<div class="row">
								<div class="cell cell-smtpUsername  col-sm-6  form-group {if !$fields['smtpAuth'].value} hide {/if}">
									<label class="field-label-smtpUsername control-label">
										{$langs['smtpUsername']} *</label>
									<div class="field field-smtpUsername">
										<input type="text" class="main-element form-control" name="smtpUsername" value="{$fields['smtpUsername'].value}">
									</div>
								</div>
							</div>			
				
							<div class="row">
								<div class="cell cell-smtpPassword  col-sm-6  form-group {if !$fields['smtpAuth'].value} hide {/if}">
									<label class="field-label-smtpPassword control-label">
										{$langs['smtpPassword']}
									</label>
									<div class="field field-smtpPassword">
										<input type="password" class="main-element form-control" name="smtpPassword" value="{$fields['smtpPassword'].value}">
									</div>
								</div>
							</div>			
						</div>
					</div>
					
								
		
	</div>
				<div class="extra"></div>
				<div class="bottom"></div>
			</div>
			<div class="side col-md-4">
			
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
	{literal}
		var installScript = new InstallScript({action: 'step4', langs: langs});
		$('.field-smtpAuth').find('input[type="checkbox"]').change( function(e){
			if ($(this).is(':checked')) {
				$('.cell-smtpPassword').removeClass('hide');
				$('.cell-smtpUsername').removeClass('hide');
			}
			else {
				$('.cell-smtpPassword').addClass('hide');
				$('.cell-smtpUsername').addClass('hide');
				$('.cell-smtpUsername').find('input').val('');
				$('.cell-smtpPassword').find('input').val('');
			}
		});
		$('[name="smtpSecurity"]').change( function(e){
				if ($(this).val() == '') {
					$('[name="smtpPort"]').val('25');			
				}
				else {
					$('[name="smtpPort"]').val('465');
				}
		});
		
	})
	{/literal}
</script>
