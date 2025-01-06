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

import Controller from 'controller';

class NoteController extends Controller {

    // noinspection JSUnusedGlobalSymbols
    /**
     * @param {Record} options
     */
    actionView(options) {
        const id = options.id;

        if (!id) {
            throw new Espo.Exceptions.NotFound;
        }

        const viewName = this.getMetadata().get(['clientDefs', this.name, 'views', 'detail']) ||
            'views/note/detail';

        let model;

        this.showLoadingNotification();

        this.modelFactory.create('Note')
            .then(m => {
                model = m;
                model.id = id;

                return model.fetch({main: true});
            })
            .then(() => {
                this.hideLoadingNotification();

                this.main(viewName, {model: model});
            });
    }
}

export default NoteController;
