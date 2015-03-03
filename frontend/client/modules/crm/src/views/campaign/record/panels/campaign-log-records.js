/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/


Espo.define('Crm:Views.Campaign.Record.Panels.CampaignLogRecords', 'Views.Record.Panels.Relationship', function (Dep) {

    return Dep.extend({

    	template: 'crm:campaign.record.panels.campaign-log-records',

    	filterList: ["All", "Sent", "Viewed", "Opted Out", "Bounced", "Clicked", "Lead Created"],

    	data: function () {
    		return _.extend({
    			filterList: this.filterList,
    			filterValue: this.filterValue,
    			filterTranslatedOptions: this.getFilterTranslatedOptions()
    		}, Dep.prototype.data.call(this));
    	},

    	getFilterTranslatedOptions: function () {
    		var o = Espo.Utils.clone(this.getLanguage().get('CampaignLogRecord', 'options', 'action'));
    		o['All'] = this.translate('All', 'labels', 'CampaignLogRecord');

    		return o;
    	},

    	setup: function () {
    		this.addEvent();

    	}

    });
});


