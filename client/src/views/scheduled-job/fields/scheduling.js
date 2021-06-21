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

define('views/scheduled-job/fields/scheduling',
    ['views/fields/varchar', 'lib!cronstrue'], function (Dep, cronstrue) {

    return Dep.extend({

        forceTrim: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.isEditMode() || this.isDetailMode()) {
                this.listenTo(this.model, 'change:' + this.name, function () {
                    this.showText();
                }, this);
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.isEditMode() || this.isDetailMode()) {
                var $text = this.$text = $('<div class="small text-success"/>');
                this.$el.append($text);
                this.showText();
            }
        },

        showText: function () {
            if (!this.$text || !this.$text.length) return;

            var exp = this.model.get(this.name);

            if (!exp) {
                this.$text.text('');
                return;
            }

            if (exp === '* * * * *') {
                this.$text.text(this.translate('As often as possible', 'labels', 'ScheduledJob'));

                return;
            }

            var locale = 'en';
            var localeList = Object.keys(cronstrue.default.locales);
            var language = this.getLanguage().name;

            if (~localeList.indexOf(language)) {
                locale = language;
            }
            else if (~localeList.indexOf(language.split('_')[0])) {
                locale = language.split('_')[0];
            }

            try {
                var text = cronstrue.toString(exp, {
                    use24HourTimeFormat: !this.getDateTime().hasMeridian(),
                    locale: locale,
                });

            }
            catch (e) {
                text = this.translate('Not valid');
            }

            this.$text.text(text);
        },

    });
});
