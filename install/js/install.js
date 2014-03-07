var InstallScript = function(opt) {
	this.reChecking = false;
	
	if (typeof(opt.action) !== 'undefined') {
		var action = opt.action;
		this.action = action;
		this[action]();
	}
	
	if (typeof(opt.langs) !== 'undefined') {
		this.langs = opt.langs;
	}
	
	if (typeof(opt.ajaxUrls) !== 'undefined') {
		this.ajaxUrls = opt.ajaxUrls;
	}
	
	this.connSett = {};
	this.userSett = {};
	this.firstUserPreferences = {};
	this.checkActions = [
		{
			'action': 'checkWritable',
			'break': true,
		}, 
		{
			'action': 'applySett',
			'break': true,
		}, 
		{
			'action': 'buildDatabse',
			'break': true,
		}, 
		{
			'action': 'createUser',
			'break': true,
		},
		{
			'action': 'checkAjaxPermission',
			'break': true,
		}
	];
	this.checkIndex = 0;
	this.checkError = false;
}

InstallScript.prototype.main = function() {
	var self = this;
	var nextAction = 'step1';
	
	$("#start").click(function(){
		$(this).attr('disabled', 'disabled');
		self.goTo(nextAction);
	})
	
	$('[name="user-lang"]').change(function(){
		self.goTo(self.action);
	})
}

InstallScript.prototype.step1 = function() {
	var self = this;
	var backAction = 'main';
	var nextAction = 'step2';
	
	$('#back').click(function(){
		$(this).attr('disabled', 'disabled');
		self.goTo(backAction);
	})
	
	$("#next").click(function(){
		$(this).attr('disabled', 'disabled');
		var licenseAgree = $('#license-agree');
		
		if (licenseAgree.length > 0 && !licenseAgree.is(':checked')) {
			$(this).removeAttr('disabled');
			self.showMsg({msg: self.getLang('You must agree to the license agreement'), error: true});
		}
		else {
			self.goTo(nextAction);
		}
	})
}

InstallScript.prototype.step2 = function() {
	var self = this;
	var backAction = 'step1';
	var nextAction = 'step3';
	
	$('#back').click(function(){
		$(this).attr('disabled', 'disabled');
		self.goTo(backAction);
	})
						
	$('#test-connection, #next').click(function(){
		$(this).attr('disabled', 'disabled');
		self.setConnSett();
		if (!self.validate()) {
			$(this).removeAttr('disabled');
			return;
		}
		self.showLoading();
		
		var btn = $(this);
		self.checkSett({
			success: function(data) {
				if (data.success) {
					if (btn.attr('id') == 'next') self.goTo(nextAction);
					else self.showMsg({msg: self.getLang('All Settings correct')});
				}
				else {
					$('#next').removeAttr('disabled');
				}
				$('#test-connection').removeAttr('disabled');
				self.hideLoading();
			}, // success END
			error: function() {
				$('#next').removeAttr('disabled');
				$('#test-connection').removeAttr('disabled');
				self.hideLoading();
			}, // success END
			
		}) // checkSett END
		
	})
}

InstallScript.prototype.step3 = function() {
	var self = this;
	var backAction = 'step2';
	var nextAction = 'step4';
	
	$('#back').click(function(){
		$(this).attr('disabled', 'disabled');
		self.goTo(backAction);
	})
	
	$("#next").click(function(){
		$(this).attr('disabled', 'disabled');
		self.setUserSett();
		if (!self.validate()) {
			$(this).removeAttr('disabled');
			return;
		}
		
		self.checkPass({
			success: function(){
				self.showLoading();
				self.actionsChecking();
				//self.goTo(nextAction);
			},
			error: function(msg) {
				$("#next").removeAttr('disabled');
				self.showMsg({msg: self.getLang(msg), error: true});
			}
		});
	})
}

InstallScript.prototype.step4 = function() {
	var self = this;
	var backAction = 'step3';
	var nextAction = 'finish';
	
	$('#back').click(function(){
		$(this).attr('disabled', 'disabled');
		self.goTo(backAction);
	})
	
	$("#next").click(function(){
		$(this).attr('disabled', 'disabled');
		self.setFirstUserPreferences();
		if (!self.validate()) {
			$(this).removeAttr('disabled');
			return;
		}
		var data = self.firstUserPreferences;
	
		data.action = 'setPreferences';
		$.ajax({
			url: "index.php",
			type: "POST",
			data: data,
			dataType: 'json',
		})
		.done(function(ajaxData){
			if (typeof(ajaxData) != 'undefined' && ajaxData.success) {
				self.goTo(nextAction);
			}
		})
		.fail(function(ajaxData){
			
		})
		
	})
}

InstallScript.prototype.errors = function() {
	var self = this;
	
	this.reChecking = true;
	var nextAction = '';
	$("#re-check").click(function(){
		$(this).attr('disabled', 'disabled');
		self.showLoading();
		self.actionsChecking();
	})
}

InstallScript.prototype.finish = function() {
	var self = this;
	
	var nextAction = '';
	$("#start").click(function(){
		self.goToEspo();
	})
}

InstallScript.prototype.setConnSett = function() {
	this.connSett.dbName = $('[name="db-name"]').val();
	this.connSett.hostName = $('[name="host-name"]').val();
	this.connSett.dbUserName = $('[name="db-user-name"]').val();
	this.connSett.dbUserPass = $('[name="db-user-password"]').val();
	this.connSett.dbDriver = $('[name="db-driver"]').val();
}

InstallScript.prototype.setUserSett = function() {
	this.userSett.name = $('[name="user-name"]').val();
	this.userSett.pass = $('[name="user-pass"]').val();
	this.userSett.confPass = $('[name="user-confirm-pass"]').val();
}

InstallScript.prototype.setFirstUserPreferences = function() {
	this.firstUserPreferences.dateFormat = $('[name="dateFormat"]').val();
	this.firstUserPreferences.timeFormat = $('[name="timeFormat"]').val();
	this.firstUserPreferences.timeZone = $('[name="timeZone"]').val();
	this.firstUserPreferences.weekStart = $('[name="weekStart"]').val();
	this.firstUserPreferences.defaultCurrency = $('[name="defaultCurrency"]').val();
	this.firstUserPreferences.thousandSeparator = $('[name="thousandSeparator"]').val();
	this.firstUserPreferences.decimalMark = $('[name="decimalMark"]').val();
	this.firstUserPreferences.language = $('[name="language"]').val();
	this.firstUserPreferences.smtpServer = $('[name="smtpServer"]').val();
	this.firstUserPreferences.smtpPort = $('[name="smtpPort"]').val();
	this.firstUserPreferences.smtpAuth = $('[name="smtpAuth"]').is(':checked');
	this.firstUserPreferences.smtpSecurity = $('[name="smtpSecurity"]').val();
	this.firstUserPreferences.smtpUsername = $('[name="smtpUsername"]').val();
	this.firstUserPreferences.smtpPassword = $('[name="smtpPassword"]').val();
	this.firstUserPreferences.outboundEmailFromName = $('[name="outboundEmailFromName"]').val();
	this.firstUserPreferences.outboundEmailFromAddress = $('[name="outboundEmailFromAddress"]').val();
	this.firstUserPreferences.outboundEmailIsShared = $('[name="outboundEmailIsShared"]').is(':checked');

}

InstallScript.prototype.checkSett = function(opt) {
	var self = this;
	this.hideMsg();
	
	var data = this.connSett;
	data.action = 'settingsTest';
	$.ajax({
		url: "index.php",
		data: data,
		type: "POST",
		dataType: 'json',
	})
	.done(function(data){
		if (typeof(data.success) !== 'undefined' && data.success) {
			data.success = true;
		}
		else {
			var msg = '';
			var rowDelim = '<br>';
			if (typeof(data.errors)) {
				var errors = data.errors;
				if (typeof(errors.phpVersion) !== 'undefined') {
					msg += self.getLang('Supported php version >=')+' '+errors.phpVersion+rowDelim;
				}
				
				if (typeof(errors.exts) !== 'undefined') {
					var exts = errors.exts;
					var len = exts.length;
					for (var index = 0; index < len; index++) {
						var temp = self.getLang('The <ext-name> PHP extension was not found...');
						temp = temp.replace('<ext-name>', exts[index]);
						msg += temp+rowDelim;
					}
				}
				
				if (typeof(errors.modRewrite) !== 'undefined') {
					msg += self.getLang('Enable mod_rewrite in Apache server')+rowDelim;
				}
				
				if (typeof(errors.dbConnect) !== 'undefined') {
					if (typeof(errors.dbConnect.errorCode) !== 'undefined') {
						var temp = self.getLang(errors.dbConnect.errorCode);
						if (temp == errors.dbConnect.errorCode && typeof(errors.dbConnect.errorMsg) !== 'undefined') temp = errors.dbConnect.errorMsg;
					}
					else if (typeof(errors.dbConnect.errorMsg) !== 'undefined') {
						temp = errors.dbConnect.errorMsg;
					}
					msg += temp+rowDelim;
				}
			}
			
			if (msg == '') {
				msg = self.getLang('Some errors occurred!');
			}
			self.showMsg({msg: msg, error: true});
		}
		
		opt.success(data);
	})
	.fail(function(){
		msg = self.getLang('Ajax failed');
		self.showMsg({msg: msg, error: true});
		opt.error();
	})
}

InstallScript.prototype.validate = function() {
	var valid = true;
	var elem = null;
	var fieldRequired = [];
	switch (this.action) {
		case 'step2':
			fieldRequired = ['db-name', 'host-name', 'db-user-name', 'db-driver'];
			break;
		case 'step3':
			fieldRequired = ['user-name', 'user-pass', 'user-confirm-pass'];
			break;
		case 'step4':
			fieldRequired = ['thousandSeparator', 'decimalMark', 'smtpUsername'];
			break;
		
		
	}
	var len = fieldRequired.length;
	for (var index = 0; index < len; index++) {
		elem = $('[name="'+fieldRequired[index]+'"]').filter(':visible');
		if (elem.length > 0) {
			if (elem.val() == '') {
				elem.parent().parent().addClass('has-error');
				valid = false;
			} 
			else {
				elem.parent().parent().removeClass('has-error');
			}
		}
	}
	
	return valid;
}


InstallScript.prototype.setForm = function(opt) {
	var formId = opt.formId || 'nav';
	var action = opt.action || 'main';
	var desc = opt.desc || '';
	
	var actionField = $('<input>', {'name': 'action', 'value': action, 'type': 'hidden'});
	$('#'+formId).append(actionField);
	var descField = $('<textarea>', {'name': 'desc', 'value': action, 'type': 'hidden'});
	descField.val(desc);
	descField.css('display', 'none');
	$('#'+formId).append(descField);
	
	$('#'+formId).attr('method', 'POST');
}

InstallScript.prototype.goTo = function(action) {	
	this.setForm({action: action});
	$('#nav').submit();
}

InstallScript.prototype.getLang = function(key) {	
	return (typeof(this.langs) !== 'undefined' && typeof(this.langs[key]) !== 'undefined')? this.langs[key] : key;
}

InstallScript.prototype.showMsg = function(opt) {
	this.hideMsg();
	
	var msg = opt.msg || '';
	var error = opt.error || false;
	$('#msg-box').html(msg);
	$('#msg-box').removeClass('hide');
	$('#msg-box').removeClass('alert-success');
	$('#msg-box').removeClass('alert-danger');
	
	if (error) $('#msg-box').addClass('alert-danger');
	else $('#msg-box').addClass('alert-success');
}

InstallScript.prototype.hideMsg = function() {	
	$('#msg-box').html('');
	$('#msg-box').addClass('hide');
}

InstallScript.prototype.showLoading = function() {	
	$('.loading-icon').removeClass('hide');
}

InstallScript.prototype.hideLoading = function() {	
	$('.loading-icon').addClass('hide');
}

InstallScript.prototype.checkPass = function(opt) {
	var succesHand = opt.success || function(){};
	var errorHand = opt.error || function(msg){};
	
	if (this.userSett.pass != this.userSett.confPass) {
		errorHand('Passwords do not match');
		return;
	}
	
	succesHand();
}

InstallScript.prototype.actionsChecking = function() {
	var self = this;
	this.checkIndex = 0;
	this.checkErrors = [];
	this.checkAction({
		'success': true,
	});
}

InstallScript.prototype.checkAction = function(dataMain) {
	var self = this;
	var data = {};
	
	if (this.checkIndex == this.checkActions.length) {
		self.callbackChecking(dataMain);
		return;
	}
	var currIndex = this.checkIndex;
	var checkAction = this.checkActions[currIndex].action;
	this.checkIndex++;
	if (checkAction == 'checkAjaxPermission') {
		this.checkAjaxPermission();
		return;
	}
	if (checkAction == 'applySett') {
		data['user-name'] = this.userSett.name;
		data['user-pass'] = this.userSett.pass;
	}
	
	data.action = checkAction;
	$.ajax({
		url: "index.php",
		type: "POST",
		data: data,
		dataType: 'json',
	})
	.done(function(ajaxData){
		if (typeof(ajaxData) != 'undefined' && ajaxData.success) {
			
			self.checkAction(ajaxData);
		}
		else {
			if (typeof(self.checkActions[currIndex]) != 'undefined' 
				&& typeof(self.checkActions[currIndex].break) != 'undefined' 
				&& self.checkActions[currIndex].break) {
				// break next checking
				self.callbackChecking(ajaxData);
			}
		}
	})
	.fail(function(ajaxData){
		if (typeof(self.checkActions[currIndex]) != 'undefined' 
			&& typeof(self.checkActions[currIndex].break) != 'undefined' 
			&& self.checkActions[currIndex].break) {
			// break next checking
			var ajaxData = {
				'success': false,
				'errorMsg': ['Ajax failed']
			}
			self.callbackChecking(ajaxData);
		}
		else {
			self.checkAction(ajaxData);
		}
	})
}

InstallScript.prototype.checkAjaxPermission = function() {
	var self = this;
	
	this.ajaxUrlFinished = 0;
	this.ajaxUrlPermRes = true;
	this.ajaxUrlPermMsgs = [];
	
	var len = this.ajaxUrls.length;
	for (var count = 0; count < len; count++) {
		var url = this.ajaxUrls[count];
		this.checkAjaxPermUrl(url);
	}
}

InstallScript.prototype.checkAjaxPermUrl = function(url) {
	var self = this;
	
	var urlAjax = '../'+url;
	var realJqXHR = $.ajax({
		url: urlAjax,
		type: "GET",
	})
	.always(function(data, textStatus, jqXHR){
		var status = jqXHR.status || realJqXHR.status || 404;
		status += '';
		if (status == '200' || status == '401') {
			var data = {'success': 1};
			self.callbackAjaxPerm(data);
		}
		else {
			var data = {};
			data.action = 'fixAjaxPermission';
			data.url = url;
			$.ajax({
				url: "index.php",
				type: "POST",
				data: data,
				dataType: 'json',
			})
			.done(function(ajaxData){
				self.callbackAjaxPerm(ajaxData);
			})
			.fail(function(){
				
				var ajaxData = {
					'success': false,
					'errorMsg': ['Ajax failed']+': '+url,
				}
				self.callbackAjaxPerm(ajaxData);
			})
		}
	})
}

InstallScript.prototype.callbackAjaxPerm = function(data) {
	this.ajaxUrlFinished++;
	if (typeof(data.success) != 'undefined' && !data.success) {
		this.ajaxUrlPermRes = false;
		this.ajaxUrlPermMsgs.push(data.errorMsg);
	}
	
	if (this.ajaxUrlFinished == this.ajaxUrls.length) {
		// all urls was checked
		var ajaxData = {
			'success': this.ajaxUrlPermRes
		}
		if (!this.ajaxUrlPermRes) {
			var errorMsg = this.getLang('Permission denied') + ':<br>' + this.ajaxUrlPermMsgs.join('<br>');
			ajaxData.errorMsg = errorMsg;
		}
		this.checkAction(ajaxData);
	}
}

InstallScript.prototype.callbackChecking = function(data) {
	this.hideLoading();
	if (typeof(data) != 'undefined' && data.success) {
		this.goTo('step4');
	}
	else {
		var desc = (typeof(data.errorMsg))? data.errorMsg : '';
		if (this.reChecking) {
			this.showMsg({msg: desc, error: true});
			$("#re-check").removeAttr('disabled');
		}
		else {
			this.setForm({action: 'errors', desc: desc});
			$('#nav').submit();
		}
	}
}

InstallScript.prototype.goToEspo = function() {
	var loc = window.location.href;
	loc = loc.replace(/install\/?/, '');
	window.location.replace(loc);
}

window.InstallScript = InstallScript;
