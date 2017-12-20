/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/


Espo.define('crm:views/campaign/record/panels/campaign-stats', 'views/record/panels/side', function (Dep) {

    return Dep.extend({


    	setupFieldList: function () {
    		var type = this.model.get('type');
    		switch (type) {
    			case 'Email':
    			case 'Newsletter':
    				this.fieldList = ['sentCount', 'openedCount', 'clickedCount', 'optedOutCount', 'bouncedCount', 'leadCreatedCount', 'revenue'];
    				break;
    			case 'Web':
    			case 'Television':
    			case 'Radio':
    				this.fieldList = ['leadCreatedCount', 'revenue'];
    				break;
    			case 'Mail':
    				this.fieldList = ['sentCount', 'leadCreatedCount', 'revenue'];
    				break;
    			default:
    				this.fieldList = ['leadCreatedCount', 'revenue'];
    		}
    	},

    	setup: function () {
    		this.fieldList = ['sentCount', 'openedCount', 'clickedCount', 'optedOutCount', 'bouncedCount', 'leadCreatedCount', 'revenue'];
            Dep.prototype.setup.call(this);
            this.setupFieldList();

            this.listenTo(this.model, 'change:type', function () {
            	this.setupFieldList();
            	if (this.isRendered()) {
            		this.reRender();
            	}
            }, this);
    	},

        actionRefresh: function () {
            this.model.fetch();
        },


    });
});


