/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import ActionHandler from 'action-handler';
import MassActionHelper from 'helpers/mass-action';
import ListRecordView from 'views/record/list';
import Ui from 'ui';
import Ajax from 'ajax';

/**
 * Important. Used for AccessToken, RefreshToken, AuthorizationCode.
 */
// noinspection JSUnusedGlobalSymbols
export default class RevokeMassActionHandler extends ActionHandler {

    async process() {
        const view = this.view as ListRecordView;

        const helper = new MassActionHelper(this.view);
        const params = view.getMassActionSelectionPostData();
        const idle = !!params.searchParams && helper.checkIsIdle(view.collection.total);

        const onDone = (count: number) => {
            const msg = this.view.translate('massRevokeDone', 'messages', 'OAuthAccessToken')
                .replace('{count}', count.toString());

            Ui.success(msg);
        };

        Ui.notifyWait();

        const result = await Ajax.postRequest('MassAction', {
            entityType: view.collection.entityType!,
            action: 'revoke',
            params: params,
            idle: idle,
        });

        if (result.id) {
            const view = await helper.process(result.id, 'revoke')

            this.view.listenToOnce(view, 'close:success', result => onDone(result.count));

            return;
        }

        onDone(result.count);
    }
}
