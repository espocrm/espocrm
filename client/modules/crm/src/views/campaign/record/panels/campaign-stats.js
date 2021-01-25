/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('crm:views/campaign/record/panels/campaign-stats', 'views/record/panels/side', function (Dep) {

    return Dep.extend({

    	controlStatsFields: function () {
    		var type = this.model.get('type');
            var fieldList = [];
    		switch (type) {
    			case 'Email':
    			case 'Newsletter':
    				fieldList = ['sentCount', 'openedCount', 'clickedCount', 'optedOutCount', 'bouncedCount', 'leadCreatedCount', 'optedInCount', 'revenue'];
    				break;
    			case 'Web':
                    fieldList = ['leadCreatedCount', 'optedInCount', 'revenue'];
                    break;
    			case 'Television':
    			case 'Radio':
    				fieldList = ['leadCreatedCount', 'revenue'];
    				break;
    			case 'Mail':
    				fieldList = ['sentCount', 'leadCreatedCount', 'optedInCount', 'revenue'];
    				break;
    			default:
    				fieldList = ['leadCreatedCount', 'revenue'];
    		}

            if (!this.getConfig().get('massEmailOpenTracking')) {
                var i = fieldList.indexOf('openedCount')
                if (~i) fieldList.splice(i, 1);
            }

            this.statsFieldList.forEach(function (item) {
                this.options.recordViewObject.hideField(item);
            }, this);

            fieldList.forEach(function (item) {
                this.options.recordViewObject.showField(item);
            }, this);

            if (!this.getAcl().checkScope('Lead')) {
                this.options.recordViewObject.hideField('leadCreatedCount');
            }

            if (!this.getAcl().checkScope('Opportunity')) {
                this.options.recordViewObject.hideField('revenue');
            }
    	},

    	setupFields: function () {
    		this.fieldList = ['sentCount', 'openedCount', 'clickedCount', 'optedOutCount', 'bouncedCount', 'leadCreatedCount', 'optedInCount', 'revenue'];
            this.statsFieldList = this.fieldList;
    	},

        setup: function () {
            Dep.prototype.setup.call(this);

            this.controlStatsFields();
            this.listenTo(this.model, 'change:type', function () {
                this.controlStatsFields();
            }, this);
        },

        actionRefresh: function () {
            this.model.fetch();
        }

    });
});
