/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import ActionHandler from 'action-handler';

class CaseDetailActionHandler extends ActionHandler {

    close() {
        const model = this.view.model;

        model.save({status: 'Closed'}, {patch: true})
            .then(() => {
                Espo.Ui.success(this.view.translate('Closed', 'labels', 'Case'));
            });
    }

    reject() {
        const model = this.view.model;

        model.save({status: 'Rejected'}, {patch: true})
            .then(() => {
                Espo.Ui.success(this.view.translate('Rejected', 'labels', 'Case'));
            });
    }

    // noinspection JSUnusedGlobalSymbols
    isCloseAvailable() {
        return this.isStatusAvailable('Closed');
    }

    // noinspection JSUnusedGlobalSymbols
    isRejectAvailable() {
        return this.isStatusAvailable('Rejected');
    }

    isStatusAvailable(status) {
        const model = this.view.model;
        const acl = this.view.getAcl();
        const metadata = this.view.getMetadata();

        if (['Closed', 'Rejected', 'Duplicate'].includes(model.get('status'))) {
            return false;
        }

        if (!acl.check(model, 'edit')) {
            return false;
        }

        if (!acl.checkField(model.entityType, 'status', 'edit')) {
            return false;
        }

        const statusList = metadata.get(['entityDefs', 'Case', 'fields', 'status', 'options']) || [];

        if (!statusList.includes(status)) {
            return false;
        }

        return true;
    }
}

export default CaseDetailActionHandler;
