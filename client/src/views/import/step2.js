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

define('views/import/step2', ['view'], function (Dep) {

    return Dep.extend({

        template: 'import/step-2',

        allowedFieldList: ['createdAt', 'createdBy'],

        events: {
            'click button[data-action="back"]': function () {
                this.back();
            },
            'click button[data-action="next"]': function () {
                this.next();
            },
            'click a[data-action="addField"]': function (e) {
                let field = $(e.currentTarget).data('name');

                this.addField(field);
            },

            'click a[data-action="removeField"]': function (e) {
                let field = $(e.currentTarget).data('name');

                this.$el.find('a[data-action="addField"]').parent().removeClass('hidden');

                let index = this.additionalFields.indexOf(field);

                if (~index) {
                    this.additionalFields.splice(index, 1);
                }

                this.$el.find('.field[data-name="' + field + '"]').parent().remove();
            },
        },

        data: function () {
            return {
                scope: this.scope,
                fieldList: this.getFieldList(),
            };
        },

        setup: function () {
            this.formData = this.options.formData;
            this.scope = this.formData.entityType;

            let mapping = [];

            this.additionalFields = [];

            if (this.formData.previewArray) {
                let index = 0;

                if (this.formData.headerRow) {
                    index = 1;
                }

                if (this.formData.previewArray.length > index) {
                    this.formData.previewArray[index].forEach((value, i) => {
                        let d = {value: value};

                        if (this.formData.headerRow) {
                            d.name = this.formData.previewArray[0][i];
                        }

                        mapping.push(d);
                    });
                }
            }

            this.wait(true);

            this.getModelFactory().create(this.scope, model => {
                this.model = model;

                if (this.formData.defaultValues) {
                    this.model.set(this.formData.defaultValues);
                }

                this.wait(false);
            });

            this.mapping = mapping;
        },

        afterRender: function () {
            let $container = $('#mapping-container');

            let $table = $('<table>')
                .addClass('table')
                .addClass('table-bordered')
                .css('table-layout', 'fixed');

            let $tbody = $('<tbody>').appendTo($table);

            let $row = $('<tr>');

            if (this.formData.headerRow) {
                let $cell = $('<th>')
                    .attr('width', '25%')
                    .text(this.translate('Header Row Value', 'labels', 'Import'));

                $row.append($cell);
            }

            let $cell = $('<th>')
                .attr('width', '25%')
                .text(this.translate('Field', 'labels', 'Import'));

            $row.append($cell);

            $cell = $('<th>').text(this.translate('First Row Value', 'labels', 'Import'));

            $row.append($cell);

            if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
                $cell = $('<th>').text(this.translate('Update by', 'labels', 'Import'));

                $row.append($cell);
            }

            $tbody.append($row);

            this.mapping.forEach((d, i) => {
                $row = $('<tr>');

                if (this.formData.headerRow) {
                    $cell = $('<td>')
                        .text(d.name);

                    $row.append($cell);
                }

                let selectedName = d.name;

                if (this.formData.attributeList) {
                    if (this.formData.attributeList[i]) {
                        selectedName = this.formData.attributeList[i];
                    } else {
                        selectedName = null;
                    }
                }

                let $select = this.getFieldDropdown(i, selectedName);

                $cell = $('<td>').append($select);

                $row.append($cell);

                var value = d.value || '';

                if (value.length > 200) {
                    value = value.substring(0, 200) + '...';
                }

                $cell = $('<td>')
                    .css('overflow', 'hidden')
                    .text(value);

                $row.append($cell);

                if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
                    let $checkbox = $('<input>')
                        .attr('type', 'checkbox')
                        .attr('id', 'update-by-' + i.toString());

                    if (!this.formData.updateBy) {
                        if (d.name === 'id') {
                            $checkbox.attr('checked', true);
                        }
                    } else {
                        if (~this.formData.updateBy.indexOf(i)) {
                            $checkbox.attr('checked', true);
                        }
                    }

                    $cell = $('<td>').append($checkbox);

                    $row.append($cell);
                }

                $tbody.append($row);
            });

            $container.empty();
            $container.append($table);

            if (this.formData.defaultFieldList) {
                this.formData.defaultFieldList.forEach((name) => {
                    this.addField(name);
                });
            }
        },

        getFieldList: function () {
            let defs = this.getMetadata().get('entityDefs.' + this.scope + '.fields');
            let forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.scope, 'edit');

            let fieldList = [];

            for (let field in defs) {
                if (~forbiddenFieldList.indexOf(field)) {
                    continue;
                }

                let d = defs[field];

                if (!~this.allowedFieldList.indexOf(field) && (d.disabled || d.importDisabled)) {
                    continue;
                }

                fieldList.push(field);
            }

            fieldList = fieldList.sort((v1, v2) => {
                return this.translate(v1, 'fields', this.scope)
                    .localeCompare(this.translate(v2, 'fields', this.scope));
            });

            return fieldList;
        },

        getAttributeList: function () {
            let fields = this.getMetadata().get(['entityDefs', this.scope, 'fields']) || {};
            let forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.scope, 'edit');

            let attributeList = [];

            attributeList.push('id');

            for (let field in fields) {
                if (~forbiddenFieldList.indexOf(field)) {
                    continue;
                }

                let d = fields[field];

                if (
                    !~this.allowedFieldList.indexOf(field) &&
                    (d.disabled && !d.importNotDisabled || d.importDisabled)
                ) {
                    continue;
                }

                if (d.type === 'phone') {
                    attributeList
                        .push(field);

                    (this.getMetadata().get('entityDefs.' + this.scope + '.fields.' + field + '.typeList') || [])
                        .map((item) => {
                            return item.replace(/\s/g, '_');
                        })
                        .forEach((item) => {
                            attributeList.push(field + Espo.Utils.upperCaseFirst(item));
                        });

                    continue;
                }

                if (d.type === 'email') {
                    attributeList.push(field + '2');
                    attributeList.push(field + '3');
                    attributeList.push(field + '4');
                }

                if (d.type === 'link') {
                    attributeList.push(field + 'Name');
                    attributeList.push(field + 'Id');
                }

                if (~['foreign'].indexOf(d.type)) {
                    continue;
                }

                if (d.type === 'personName') {
                    attributeList.push(field);
                }

                var type = d.type;
                var actualAttributeList = this.getFieldManager().getActualAttributeList(type, field);

                if (!actualAttributeList.length) {
                    actualAttributeList = [field];
                }

                actualAttributeList.forEach((f) => {
                    if (attributeList.indexOf(f) === -1) {
                        attributeList.push(f);
                    }
                });
            }

            attributeList = attributeList.sort((v1, v2) => {
                return this.translate(v1, 'fields', this.scope)
                    .localeCompare(this.translate(v2, 'fields', this.scope));
            });

            return attributeList;
        },

        getFieldDropdown: function (num, name) {
            name = name || false;

            let fieldList = this.getAttributeList();

            let $select = $('<select>')
                .addClass('form-control')
                .attr('id', 'column-' + num.toString());

            let $option = $('<option>')
                .val('')
                .text('-' + this.translate('Skip', 'labels', 'Import') + '-');

            let scope = this.formData.entityType;

            $select.append($option);

            fieldList.forEach(field => {
                let label = '';

                if (
                    this.getLanguage().has(field, 'fields', scope) ||
                    this.getLanguage().has(field, 'fields', 'Global')
                ) {
                    label = this.translate(field, 'fields', scope);
                }
                else {
                    if (field.indexOf('Id') === field.length - 2) {
                        let baseField = field.substr(0, field.length - 2);

                        if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                            label = this.translate(baseField, 'fields', scope) +
                                ' (' + this.translate('id', 'fields') + ')';
                        }
                    }
                    else if (field.indexOf('Name') === field.length - 4) {
                        let baseField = field.substr(0, field.length - 4);

                        if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                            label = this.translate(baseField, 'fields', scope) +
                                ' (' + this.translate('name', 'fields') + ')';
                        }
                    }
                    else if (field.indexOf('Type') === field.length - 4) {
                        let baseField = field.substr(0, field.length - 4);

                        if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                            label = this.translate(baseField, 'fields', scope) +
                                ' (' + this.translate('type', 'fields') + ')';
                        }
                    }
                    else if (field.indexOf('phoneNumber') === 0) {
                        let phoneNumberType = field.substr(11);

                        let phoneNumberTypeLabel = this.getLanguage()
                            .translateOption(phoneNumberType, 'phoneNumber', scope);

                        label = this.translate('phoneNumber', 'fields', scope) +
                            ' (' + phoneNumberTypeLabel + ')';
                    }
                    else if (
                        field.indexOf('emailAddress') === 0 &&
                        parseInt(field.substr(12)).toString() === field.substr(12)
                    ) {
                        let emailAddressNum = field.substr(12);

                        label = this.translate('emailAddress', 'fields', scope) + ' ' + emailAddressNum.toString();
                    }
                    else if (field.indexOf('Ids') === field.length - 3) {
                        let baseField = field.substr(0, field.length - 3);

                        if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                            label = this.translate(baseField, 'fields', scope) +
                                ' (' + this.translate('ids', 'fields') + ')';
                        }
                    }
                }

                if (!label) {
                    label = field;
                }

                $option = $('<option>')
                    .val(field)
                    .text(label);

                if (name) {
                    if (field === name) {
                        $option.prop('selected', true);
                    }
                    else {
                        if (name.toLowerCase().replace('_', '') === field.toLowerCase()) {
                            $option.prop('selected', true);
                        }
                    }
                }

                $select.append($option);
            });

            return $select;
        },

        addField: function (name) {
            this.$el.find('[data-action="addField"][data-name="' + name + '"]')
                .parent()
                .addClass('hidden');

            $(this.containerSelector + ' button[data-name="update"]').removeClass('disabled');

            Espo.Ui.notify(' ... ');

            let label = this.translate(name, 'fields', this.scope);
            label = this.getHelper().escapeString(label);

            let removeLink =
                '<a role="button" class="pull-right" data-action="removeField" data-name="' + name + '">' +
                '<span class="fas fa-times"></span></a>';

            let html =
                '<div class="cell form-group">' + removeLink + '<label class="control-label">' + label +
                '</label><div class="field" data-name="' + name + '"/></div>';

            $('#default-values-container').append(html);

            let type = Espo.Utils.upperCaseFirst(this.model.getFieldParam(name, 'type'));

            let viewName =
                this.getMetadata().get(['entityDefs', this.scope, 'fields', name, 'view']) ||
                this.getFieldManager().getViewName(type);

            this.createView(name, viewName, {
                model: this.model,
                fullSelector: this.getSelector() + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                },
                mode: 'edit',
                readOnlyDisabled: true,
            }, view => {
                this.additionalFields.push(name);

                view.render();
                view.notify(false);
            });
        },

        disableButtons: function () {
            this.$el.find('button[data-action="next"]').addClass('disabled').attr('disabled', 'disabled');
            this.$el.find('button[data-action="back"]').addClass('disabled').attr('disabled', 'disabled');
        },

        enableButtons: function () {
            this.$el.find('button[data-action="next"]').removeClass('disabled').removeAttr('disabled');
            this.$el.find('button[data-action="back"]').removeClass('disabled').removeAttr('disabled');
        },

        fetch: function (skipValidation) {
            let attributes = {};

            this.additionalFields.forEach(field => {
                let view = this.getView(field);

                _.extend(attributes, view.fetch());
            });

            this.model.set(attributes);

            let notValid = false;

            this.additionalFields.forEach(field => {
                let view = this.getView(field);

                notValid = view.validate() || notValid;
            });

            if (!notValid) {
                this.formData.defaultValues = attributes;
            }

            if (notValid && !skipValidation) {
                return false;
            }

            this.formData.defaultFieldList = Espo.Utils.clone(this.additionalFields);

            var attributeList = [];

            this.mapping.forEach((d, i) => {
                attributeList.push($('#column-' + i).val());
            });

            this.formData.attributeList = attributeList;

            if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
                let updateBy = [];

                this.mapping.forEach((d, i) => {
                    if ($('#update-by-' + i).get(0).checked) {
                        updateBy.push(i);
                    }
                });

                this.formData.updateBy = updateBy;
            }

            this.getParentView().formData = this.formData;
            this.getParentView().trigger('change');

            return true;
        },

        back: function () {
            this.fetch(true);

            this.getParentView().changeStep(1);
        },

        next: function () {
            if (!this.fetch()) {
                return;
            }

            this.disableButtons();

            Espo.Ui.notify(' ... ');

            Espo.Ajax.postRequest('Import/file', null, {
                timeout: 0,
                contentType: 'text/csv',
                data: this.getParentView().fileContents,
            }).then(result => {
                if (!result.attachmentId) {
                    Espo.Ui.error(this.translate('Bad response'));

                    return;
                }

                this.runImport(result.attachmentId);
            });
        },

        runImport: function (attachmentId) {
            this.formData.attachmentId = attachmentId;

            this.getRouter().confirmLeaveOut = false;

            Espo.Ui.notify(this.translate('importRunning', 'messages', 'Import'));

            Espo.Ajax.postRequest('Import', this.formData, {timeout: 0})
                .then(result => {
                    let id = result.id;

                    this.getParentView().trigger('done');

                    if (!id) {
                        Espo.Ui.error(this.translate('Error'), true);

                        this.enableButtons();

                        return;
                    }

                    if (!this.formData.manualMode) {
                        this.getRouter().navigate('#Import/view/' + id, {trigger: true});

                        Espo.Ui.notify(false);

                        return;
                    }

                    this.createView('dialog', 'views/modal', {
                        templateContent: "{{complexText viewObject.options.msg}}",
                        headerText: ' ',
                        backdrop: 'static',
                        msg:
                            this.translate('commandToRun', 'strings', 'Import') + ':\n\n' +
                            '```php command.php import --id='+id+'```',
                        buttonList: [
                            {
                                name: 'close',
                                label: this.translate('Close'),
                            }
                        ],
                    }, view => {
                        view.render();

                        this.listenToOnce(view, 'close', () => {
                            this.getRouter().navigate('#Import/view/' + id, {trigger: true});
                        });
                    });

                    Espo.Ui.notify(false);
                })
                .catch(() => this.enableButtons());
        },
    });
});
