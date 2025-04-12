/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

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

	if (typeof(opt.modRewriteUrl) !== 'undefined') {
		this.modRewriteUrl = opt.modRewriteUrl;
	}

	if (typeof(opt.apiPath) !== 'undefined') {
		this.apiPath = opt.apiPath.substr(1) + '/';
	}

	if (typeof(opt.serverType) !== 'undefined') {
		this.serverType = opt.serverType;
	}

	if (typeof(opt.OS) !== 'undefined') {
		this.OS = opt.OS;
	}

	this.connSett = {};
	this.userSett = {};
	this.systemSettings = {};
	this.emailSettings = {};
	this.checkActions = [
		{
			'action': 'checkPermission',
			'break': true
		},
		{
			'action': 'saveSettings',
			'break': true
		},
        {
            'action': 'checkModRewrite',
            'break': true
        },
		{
			'action': 'buildDatabase',
			'break': true
		}

	];
	this.checkIndex = 0;
	this.checkError = false;

    this.initSanitizeHtml();
}

InstallScript.prototype.main = function() {
	var self = this;
	var nextAction = 'step1';

	$("#start").click(function(){
		$(this).attr('disabled', 'disabled');
		self.goTo(nextAction);
	});

	$('[name="user-lang"]').change(() => {
		this.goTo(self.action);
	});

	$('[name="theme"]').change(() => {
		this.goTo(self.action);
	});
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
			self.showMsg({msg: self.getLang('You must agree to the license agreement', 'messages'), error: true});
		}
		else {
			self.goTo(nextAction);
		}
	})
}

InstallScript.prototype.step2 = function() {
	var self = this;
	var backAction = 'step1';
	var nextAction = 'setupConfirmation';

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
					else self.showMsg({msg: self.getLang('All Settings correct', 'messages')});
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
			}, // error END

		}) // checkSett END

	})
}

InstallScript.prototype.setupConfirmation = function() {
    const self = this;
    const backAction = 'step2';

    $('#back').click(function(){

		$(this).attr('disabled', 'disabled');
		self.goTo(backAction);
	})

	$("#next").click(function(){
		$(this).attr('disabled', 'disabled');
		self.showLoading();
		self.actionsChecking();
	})
}

InstallScript.prototype.step3 = function() {
    const self = this;
    const backAction = '';
    const nextAction = 'step4';

    $('input[name="user-name"]').blur(function(){
        let value = $(this).val();
        value = value.replace(/[^a-z0-9_\s]/gi, '').replace(/[\s]/g, '_').toLowerCase();
		$(this).val(value);
	})

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
                const data = self.userSett;

                data['user-name'] = self.userSett.name;
				data['user-pass'] = self.userSett.pass;

				data.action = 'createUser';
				$.ajax({
					url: "index.php",
					type: "POST",
					data: data,
					dataType: 'json',
				})
				.done(function(ajaxData){
					if (typeof(ajaxData) != 'undefined' && ajaxData.success) {
						self.goTo(nextAction);
					} else {
						$("#next").removeAttr('disabled');
						self.showMsg({msg: self.getLang(ajaxData.errorMsg, 'messages'), error: true});
					}
				})
			},
			error: function(msg) {
				$("#next").removeAttr('disabled');
				self.showMsg({msg: self.getLang(msg, 'messages'), error: true});
			}
		});
	})
}

InstallScript.prototype.step4 = function() {
    const self = this;
    const backAction = 'step3';
    const nextAction = 'finish';

    $('#back').click(function(){
		$(this).attr('disabled', 'disabled');
		self.goTo(backAction);
	})

	$("#next").click(function(){
		$(this).attr('disabled', 'disabled');

		self.setSystemSett();
		if (!self.validate()) {
			$(this).removeAttr('disabled');
			return;
		}

        const data = self.systemSettings;

        data.action = 'savePreferences';

		$.ajax({
			url: "index.php",
			type: "POST",
			data: data,
			dataType: 'json',
		})
		.done(ajaxData => {
			if (typeof(ajaxData) != 'undefined' && ajaxData.success) {
				self.goTo(nextAction);
			}
		});
	})
}

InstallScript.prototype.errors = function() {
    const self = this;

    this.reChecking = true;

	$("#re-check").click(function () {
		$(this).attr('disabled', 'disabled');

		self.showLoading();
		self.actionsChecking();
	})
}

InstallScript.prototype.finish = function() {
    const self = this;

    $("#start").click(() => {
		self.goToEspo();
	})
}

InstallScript.prototype.setConnSett = function() {
    this.connSett.dbPlatform = $('[name="db-platform"]').val();
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

InstallScript.prototype.setSystemSett = function() {
	this.systemSettings.dateFormat = $('[name="dateFormat"]').val();
	this.systemSettings.timeFormat = $('[name="timeFormat"]').val();
	this.systemSettings.timeZone = $('[name="timeZone"]').val();
	this.systemSettings.weekStart = $('[name="weekStart"]').val();
	this.systemSettings.defaultCurrency = $('[name="defaultCurrency"]').val();
	this.systemSettings.thousandSeparator = $('[name="thousandSeparator"]').val();
	this.systemSettings.decimalMark = $('[name="decimalMark"]').val();
	this.systemSettings.language = $('[name="language"]').val();
}

InstallScript.prototype.checkSett = function(opt) {
    const self = this;
    this.hideMsg();

    const data = this.connSett;
    data.action = 'settingsTest';

	$.ajax({
		url: "index.php",
		data: data,
		type: "POST",
		dataType: 'json',
	})
	.done(data => {
		if (typeof(data.success) !== 'undefined' && data.success) {
			data.success = true;
		} else {
            let msg = '';
            const rowDelim = '<br>';

            if (typeof(data.errors)) {
                const errors = data.errors;

                Object.keys(errors).forEach(function (errorName) {
                    const errorData = errors[errorName];

                    switch(errorName) {
					    case 'phpRequires':
                            const len = errorData.length;

                            for (let index = 0; index < len; index++) {
								let temp = self.getLang('The PHP extension was not found...', 'messages');

								temp = temp.replace('{extName}', errorData[index]);

								msg += temp + rowDelim;
							}

					        break;

					    case 'modRewrite':
					        msg += errorData + rowDelim;

					        break;

					    case 'dbConnect':
                            let temp;

					        if (typeof(errorData.errorCode) !== 'undefined') {
								temp = self.getLang(errorData.errorCode, 'messages');

								if (temp == errorData.errorCode && typeof(errorData.errorMsg) !== 'undefined') {
                                    temp = errorData.errorMsg;
                                }
							}
							else if (typeof(errorData.errorMsg) !== 'undefined') {
								temp = errorData.errorMsg;
							}

							msg += temp + rowDelim;

					        break;

					    default:
					        msg += self.getLang(errorName, 'messages').replace('{minVersion}', errorData) + rowDelim;
					}

				});
			}

			if (msg == '') {
				msg = self.getLang('Some errors occurred!', 'messages');
			}

			self.showMsg({msg: msg, error: true});
		}

		opt.success(data);
	})
	.fail(() => {
        const msg = self.getLang('Ajax failed', 'messages');
        self.showMsg({msg: msg, error: true});
		opt.error();
	})
}

InstallScript.prototype.validate = function() {
	this.hideMsg();

    let valid = true;
    let elem = null;
    let fieldRequired = [];

    switch (this.action) {
		case 'step2':
			fieldRequired = ['db-name', 'host-name', 'db-user-name', 'db-driver'];
			break;

		case 'step3':
			fieldRequired = ['user-name', 'user-pass', 'user-confirm-pass'];
			break;

		case 'step4':
			fieldRequired = ['decimalMark'];
			break;
	}

    const len = fieldRequired.length;

    for (let index = 0; index < len; index++) {
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

	// decimal and group sep
	$('[name="thousandSeparator"]').parent().parent().removeClass('has-error');

	if (typeof(this.systemSettings.thousandSeparator) !== 'undefined'
		&& typeof(this.systemSettings.decimalMark) !== 'undefined'
		&& this.systemSettings.thousandSeparator == this.systemSettings.decimalMark
		&& valid) {

		$('[name="thousandSeparator"]').parent().parent().addClass('has-error');
		$('[name="decimalMark"]').parent().parent().addClass('has-error');

        const msg = this.getLang('Thousand Separator and Decimal Mark equal', 'messages');
        this.showMsg({msg: msg, error: true});
		valid = false;
	}

	return valid;
}

InstallScript.prototype.setForm = function(opt) {
    const formId = opt.formId || 'nav';
    const action = opt.action || 'main';

    const actionField = $('<input>', {'name': 'action', 'value': action, 'type': 'hidden'});

    $('#'+formId).append(actionField);
	$('#'+formId).attr('method', 'POST');
}

InstallScript.prototype.goTo = function(action) {
	this.setForm({action: action});
	$('#nav').submit();
}

InstallScript.prototype.getLang = function(key, type) {
	return (
        typeof(this.langs) !== 'undefined' &&
        typeof(this.langs[type]) !== 'undefined' &&
        typeof(this.langs[type][key]) !== 'undefined'
    ) ? this.langs[type][key] : key;
}

InstallScript.prototype.showMsg = function(opt) {
	this.hideMsg();

    let message = opt.msg || '';
    const error = opt.error || false;

    if (message) {
        message = this.sanitizeHtml(message);

        $('#msg-box').html(message);
        $('#msg-box').removeClass('hide');
        $('#msg-box').removeClass('alert-success');
        $('#msg-box').removeClass('alert-danger');
    }

	if (error) {
        $('#msg-box').addClass('alert-danger');
    } else {
        $('#msg-box').addClass('alert-success');
    }
}

InstallScript.prototype.initSanitizeHtml = function() {
    DOMPurify.addHook('afterSanitizeAttributes', function(node) {
        if ('target' in node) {
            node.setAttribute('target','_blank');
        }
    });
}

InstallScript.prototype.sanitizeHtml = function(html) {
	return DOMPurify.sanitize(html);
}

InstallScript.prototype.hideMsg = function() {
	$('#msg-box').html('');
	$('#msg-box').addClass('hide');
}

InstallScript.prototype.showLoading = function() {
    Espo.Ui.notifyWait();
}

InstallScript.prototype.hideLoading = function() {
    Espo.Ui.notify(false);
}

InstallScript.prototype.checkPass = function(opt) {
    const successHand = opt.success || (() => {});
    const errorHand = opt.error || (() => {});

    if (this.userSett.pass !== this.userSett.confPass) {
		errorHand('Passwords do not match');

		return;
	}

	successHand();
}

InstallScript.prototype.actionsChecking = function() {
	this.checkIndex = 0;
	this.checkErrors = [];

	this.checkAction({
		'success': true,
	});
}

InstallScript.prototype.checkAction = function(dataMain) {
	var self = this;
	var data = {};

	if (this.checkIndex === this.checkActions.length) {
		self.callbackChecking(dataMain);
		return;
	}

	var currIndex = this.checkIndex;
	var checkAction = this.checkActions[currIndex].action;
	this.checkIndex++;

	if (checkAction === 'checkModRewrite') {
		this.checkModRewrite();
		return;
	}
	if (checkAction === 'saveSettings') {
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
	.done(ajaxData => {
		if (typeof(ajaxData) != 'undefined' && ajaxData.success) {

			self.checkAction(ajaxData);
		} else {
			if (typeof(self.checkActions[currIndex]) != 'undefined'
				&& typeof(self.checkActions[currIndex].break) != 'undefined'
				&& self.checkActions[currIndex].break) {
				// break next checking
				self.callbackChecking(ajaxData);
			}
		}
	})
	.fail(ajaxData => {
		if (
            typeof(self.checkActions[currIndex]) != 'undefined'
			&& typeof(self.checkActions[currIndex].break) != 'undefined'
			&& self.checkActions[currIndex].break) {
			// break next checking

			ajaxData = {
				'success': false,
				'errorMsg': [self.getLang('Ajax failed', 'messages')]
			}
			self.callbackChecking(ajaxData);
		}
		else {
			self.checkAction(ajaxData);
		}
	})
}

InstallScript.prototype.checkModRewrite = function() {
	var self = this;
	this.modRewriteUrl;

    const urlAjax = '..' + this.modRewriteUrl;
    var realJqXHR = $.ajax({
		url: urlAjax,
		type: "GET",
	})
	.always(function(data, textStatus, jqXHR){
        let status = jqXHR.status || realJqXHR.status || 404;
        status += '';

		var data = {'success': 0};

		if (status == '200' || status == '401') {
			var data = {'success': 1};
		}

		self.callbackModRewrite(data);
	})
}

InstallScript.prototype.callbackModRewrite = function(data) {
	var ajaxData = {
		'success': true,
	}

	if (typeof(data.success) != 'undefined' && data.success) {
		this.checkAction(ajaxData);
		return;
	}

	ajaxData.success = false;
	ajaxData.errorMsg = this.getModRewriteErrorMessage();

    const realCheckIndex = this.checkIndex - 1;

    if (
        typeof(this.checkActions[realCheckIndex]) != 'undefined' &&
        typeof(this.checkActions[realCheckIndex].break) != 'undefined'
    ) {
		if (this.checkActions[realCheckIndex].break) {
			// break next checking
			this.callbackChecking(ajaxData);
		} else {
			this.checkAction(ajaxData);
		}
	}
}

InstallScript.prototype.callbackChecking = function(data) {
	this.hideLoading();

	if (typeof(data) != 'undefined' && data.success) {
		this.goTo('step3');
	} else {
        let errorMsg = (typeof (data.errorMsg)) ? data.errorMsg : '';

        errorMsg += (typeof(data.errorFixInstruction) != 'undefined')? data.errorFixInstruction : '';

		if (this.reChecking) {
			this.showMsg({msg: errorMsg, error: true});
			$("#re-check").removeAttr('disabled');
		} else {
			this.setForm({action: 'errors'});
			$('#nav').submit();
		}
	}
}

InstallScript.prototype.getEspoPath = function(onlyPath) {
	onlyPath = typeof onlyPath !== 'undefined' ? onlyPath : false;

    let location = window.location.href;

    if (onlyPath) {
		location = window.location.pathname;
	}

	location = location.replace(/install\/?/, '');

	return location;
}

InstallScript.prototype.getModRewriteErrorMessage = function() {
    let message = '';

    if (typeof(this.langs) !== 'undefined') {
		message = (typeof(this.langs['options']['modRewriteTitle'][this.serverType]) !== 'undefined') ?
            this.langs['options']['modRewriteTitle'][this.serverType] :
            this.langs['options']['modRewriteTitle']['default'];

		message += (
            typeof(this.langs['options']['modRewriteInstruction'][this.serverType]) !== 'undefined' &&
            typeof(this.langs['options']['modRewriteInstruction'][this.serverType][this.OS]) !== 'undefined'
        ) ? this.langs['options']['modRewriteInstruction'][this.serverType][this.OS] : '';

		message = message
            .replace("{ESPO_PATH}", this.getEspoPath(true))
            .replace("{API_PATH}", this.apiPath).replace("{API_PATH}", this.apiPath);
	}

	return message;
}

InstallScript.prototype.goToEspo = function() {
    const location = this.getEspoPath();

    window.location.replace(location);
}

window.InstallScript = InstallScript;
