/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('Views.Fields.MultiEnum', ['Views.Fields.Array', 'lib!Select2'], function (Dep, Select2) {

    return Dep.extend({

        type: 'multiEnum',

        listTemplate: 'fields.array.detail',

        detailTemplate: 'fields.array.detail',

        editTemplate: 'fields.multi-enum.edit',        
        
        events: {
        },
        
        data: function () {
            return _.extend({
                optionList: this.params.options || []
            }, Dep.prototype.data.call(this));
        },
        
        getTranslatedOptions: function () {
            return (this.params.options || []).map(function (item) {
                if (this.translatedOptions != null) {
                    if (item in this.translatedOptions) {
                        return this.translatedOptions[item];
                    }
                }
                return item;
            });
        },        
        

        setup: function () {
            Dep.prototype.setup.call(this);
        },
        
        afterRender: function () {             
            if (this.mode == 'edit' || this.mode == 'search') {
                var $element = this.$element = this.$el.find('[name="' + this.name + '"]');
                this.$element.val(this.selected.join(','));
                
                this.$element.select2({
                    data: (this.params.options || []).map(function (item) {
                        var text = item;
                        if (this.translatedOptions) {
                            if (item in this.translatedOptions) {
                                text = this.translatedOptions[item];
                            }
                        }
                        return {
                            id: item,
                            text: text
                        };
                    }, this),
                    multiple: true,
                    formatSearching: '',
                    formatNoMatches: '',
                    matcher: function (term, text) {
                        return text.toUpperCase().indexOf(term.toUpperCase()) == 0;
                    }
                });
                
                this.$element.on('change', function () {
                    this.trigger('change');
                }.bind(this));

                
                this.$element.select2('container').find('ul.select2-choices').sortable({
                    containment: 'parent',
                    start: function () {
                        $element.select2('onSortStart');
                    },
                    update: function () {
                        $element.select2('onSortEnd');
                    }
                });
            }
        },

        fetch: function () {
            var list = this.$element.val().split(',');
            if (list.length == 1 && list[0] == '') {
                list = [];
            } 
            var data = {};
            data[this.name] = list;
            return data;
        },

        validateRequired: function () {                
            if (this.params.required || this.model.isRequired(this.name)) {
                var value = this.model.get(this.name);
                if (!value || value.length == 0) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg, '.select2-container');
                    return true;
                }
            }
        },

    });
});


