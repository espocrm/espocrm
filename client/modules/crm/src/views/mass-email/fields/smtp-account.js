/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('crm:views/mass-email/fields/smtp-account', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        dataUrl: 'MassEmail/action/smtpAccountDataList',

        getAttributeList: function () {
            return [this.name, 'inboundEmailId'];
        },

        setupOptions: function () {
            Dep.prototype.setupOptions.call(this);

            this.params.options = [];
            this.translatedOptions = {};

            this.params.options.push('system');

            if (!this.loadedOptionList) {
                if (this.model.get('inboundEmailId')) {
                    var item = 'inboundEmail:' + this.model.get('inboundEmailId');
                    this.params.options.push(item);
                    this.translatedOptions[item] =
                        (this.model.get('inboundEmailName') || this.model.get('inboundEmailId')) + ' (' + this.translate('group', 'labels', 'MassEmail') + ')';
                }
            } else {
                this.loadedOptionList.forEach(function (item) {
                     this.params.options.push(item);
                     this.translatedOptions[item] =
                        (this.loadedOptionTranslations[item] || item) + ' (' + this.translate('group', 'labels', 'MassEmail') + ')';
                }, this);
            }

            this.translatedOptions['system'] =
                this.getConfig().get('outboundEmailFromAddress') + ' (' + this.translate('system', 'labels', 'MassEmail') + ')';
        },

        getValueForDisplay: function () {
            if (!this.model.has(this.name)) {
                if (this.model.has('inboundEmailId')) {
                    if (this.model.get('inboundEmailId')) {
                        return 'inboundEmail:' + this.model.get('inboundEmailId');
                    } else {
                        return 'system';
                    }
                } else {
                    return '...';
                }
            }

            return this.model.get(this.name);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getAcl().checkScope('MassEmail', 'create') || this.getAcl().checkScope('MassEmail', 'edit')) {
                this.ajaxGetRequest(this.dataUrl).then(function (dataList) {
                    if (!dataList.length) return;
                    this.loadedOptionList = [];
                    this.loadedOptionTranslations = {};
                    this.loadedOptionAddresses = {};
                    this.loadedOptionFromNames = {};
                    dataList.forEach(function (item) {
                        this.loadedOptionList.push(item.key);
                        this.loadedOptionTranslations[item.key] = item.emailAddress;
                        this.loadedOptionAddresses[item.key] = item.emailAddress;
                        this.loadedOptionFromNames[item.key] = item.fromName || '';
                    }, this);
                    this.setupOptions();
                    this.reRender();
                }.bind(this));
            }
        },

        fetch: function () {
            var data = {};
            var value = this.$element.val();
            data[this.name] = value;

            if (!value || value === 'system') {
                data.inboundEmailId = null;
                data.inboundEmailName = null;
            } else {
                var arr = value.split(':');
                if (arr.length > 1) {
                    data.inboundEmailId = arr[1];
                    data.inboundEmailName = this.translatedOptions[data.inboundEmailId] || data.inboundEmailId;
                }
            }

            return data;
        }

    });
});
