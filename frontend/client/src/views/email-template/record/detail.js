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

Espo.define('views/email-template/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        duplicateAction: true,

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.listenToInsertField();
        },

        listenToInsertField: function () {
            var fieldView = this.getFieldView('insertField');
            if (fieldView) {
                this.listenTo(fieldView, 'insert-field', function (o) {
                    var tag = '{' + o.entityType + '.' + o.field + '}';

                    $body = this.$el.find('[name="body"]');

                    if (this.model.get('isHtml')) {
                        var code = $body.summernote().code();
                        code += tag;
                        $body.summernote().code(code);
                    } else {
                        var text = $body.val();
                        text += tag;
                        $body.val(text);
                    }

                    var bodyView = this.getFieldView('body');


                }.bind(this));
            };
        },

    });

});
