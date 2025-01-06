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

define('crm:views/campaign-log-record/fields/data', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        listTemplate: 'crm:campaign-log-record/fields/data/detail',

    	getValueForDisplay: function () {
    		let action = this.model.get('action');

    		switch (action) {
    			case 'Sent':
                case 'Opened':
                    if (
                        this.model.get('objectId') &&
                        this.model.get('objectType') &&
                        this.model.get('objectName')
                    ) {
                        return $('<a>')
                            .attr('href', '#' + this.model.get('objectType') + '/view/' + this.model.get('objectId'))
                            .text(this.model.get('objectName'))
                            .get(0).outerHTML;
                    }

                    return $('<span>')
                        .text(this.model.get('stringData') || '')
                        .get(0).outerHTML;

    			case 'Clicked':
                    if (
                        this.model.get('objectId') &&
                        this.model.get('objectType') &&
                        this.model.get('objectName')
                    ) {
                        return $('<a>')
                            .attr('href', '#' + this.model.get('objectType') + '/view/' + this.model.get('objectId'))
                            .text(this.model.get('objectName'))
                            .get(0).outerHTML;
                    }

                    return $('<span>')
                        .text(this.model.get('stringData') || '')
                        .get(0).outerHTML;

                case 'Opted Out':
                    return $('<span>')
                        .text(this.model.get('stringData') || '')
                        .addClass('text-danger')
                        .get(0).outerHTML;

                case 'Bounced':
                    let emailAddress = this.model.get('stringData');
                    let type = this.model.get('stringAdditionalData');

                    let typeLabel = type === 'Hard' ?
                        this.translate('hard', 'labels', 'Campaign') :
                        this.translate('soft', 'labels', 'Campaign')

                    return $('<span>')
                        .append(
                            $('<span>')
                                .addClass('label label-default')
                                .text(typeLabel),
                            ' ',
                            $('<s>')
                                .text(emailAddress)
                                .addClass(type === 'Hard' ? 'text-danger' : '')
                        )
                        .get(0).outerHTML;
    		}

    		return '';
    	},
    });
});
