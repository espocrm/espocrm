/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import View from 'view';

class Step2ImportView extends View {

    template = 'import/step-2'

    allowedFieldList = ['createdAt', 'createdBy']

    events = {
        /** @this Step2ImportView */
        'click button[data-action="back"]': function () {
            this.back();
        },
        /** @this Step2ImportView */
        'click button[data-action="next"]': function () {
            this.next();
        },
        /** @this Step2ImportView */
        'click a[data-action="addField"]': function (e) {
            const field = $(e.currentTarget).data('name');

            this.addField(field);
        },
        /** @this Step2ImportView */
        'click a[data-action="removeField"]': function (e) {
            const field = $(e.currentTarget).data('name');

            this.$el.find('a[data-action="addField"]').parent().removeClass('hidden');

            const index = this.additionalFields.indexOf(field);

            if (~index) {
                this.additionalFields.splice(index, 1);
            }

            this.$el.find('.field[data-name="' + field + '"]').parent().remove();
        },
    }

    data() {
        return {
            scope: this.scope,
            fieldList: this.getFieldList(),
        };
    }

    setup() {
        this.formData = this.options.formData;
        this.scope = this.formData.entityType;

        const mapping = [];

        this.additionalFields = [];

        if (this.formData.previewArray) {
            let index = 0;

            if (this.formData.headerRow) {
                index = 1;
            }

            if (this.formData.previewArray.length > index) {
                this.formData.previewArray[index].forEach((value, i) => {
                    const d = {value: value};

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
    }

    afterRender() {
        const $container = $('#mapping-container');

        const $table = $('<table>')
            .addClass('table')
            .addClass('table-bordered')
            .css('table-layout', 'fixed');

        const $tbody = $('<tbody>').appendTo($table);

        let $row = $('<tr>');

        if (this.formData.headerRow) {
            const $cell = $('<th>')
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

            const $select = this.getFieldDropdown(i, selectedName);

            $cell = $('<td>').append($select);

            $row.append($cell);

            let value = d.value || '';

            if (value.length > 200) {
                value = value.substring(0, 200) + '...';
            }

            $cell = $('<td>')
                .css('overflow', 'hidden')
                .text(value);

            $row.append($cell);

            if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
                const $checkbox = $('<input>')
                    .attr('type', 'checkbox')
                    .addClass('form-checkbox')
                    .attr('id', 'update-by-' + i.toString());

                /** @type {HTMLInputElement} */
                const checkboxElement = $checkbox.get(0);

                if (!this.formData.updateBy) {
                    if (d.name === 'id') {
                        checkboxElement.checked = true;
                    }
                }
                else if (~this.formData.updateBy.indexOf(i)) {
                    checkboxElement.checked = true;
                }

                $cell = $('<td>').append(checkboxElement);

                $row.append($cell);
            }

            $tbody.append($row);
        });

        $container.empty();
        $container.append($table);

        this.getDefaultFieldList().forEach(name => {
            this.addField(name);
        });
    }

    /**
     * @return {string[]}
     */
    getDefaultFieldList() {
        if (this.formData.defaultFieldList) {
            return this.formData.defaultFieldList;
        }

        if (!this.formData.defaultValues) {
            return [];
        }

        const defaultAttributes = Object.keys(this.formData.defaultValues);

        return this.getFieldManager().getEntityTypeFieldList(this.scope)
            .filter(field => {
                const attributeList = this.getFieldManager()
                    .getEntityTypeFieldActualAttributeList(this.scope, field);

                return attributeList.findIndex(attribute => defaultAttributes.includes(attribute)) !== -1;
            })
    }

    getFieldList() {
        const defs = this.getMetadata().get('entityDefs.' + this.scope + '.fields');
        const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.scope, 'edit');

        let fieldList = [];

        for (const field in defs) {
            if (~forbiddenFieldList.indexOf(field)) {
                continue;
            }

            const d = /** @type {Object.<string, *>} */defs[field];

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
    }

    getAttributeList() {
        const fields = this.getMetadata().get(['entityDefs', this.scope, 'fields']) || {};
        const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.scope, 'edit');

        let attributeList = [];

        attributeList.push('id');

        for (const field in fields) {
            if (~forbiddenFieldList.indexOf(field)) {
                continue;
            }

            const d = /** @type {Object.<string, *>} */fields[field];

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

            const type = d.type;
            let actualAttributeList = this.getFieldManager().getActualAttributeList(type, field);

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
    }

    getFieldDropdown(num, name) {
        name = name || false;

        const fieldList = this.getAttributeList();

        const $select = $('<select>')
            .addClass('form-control')
            .attr('id', 'column-' + num.toString());

        let $option = $('<option>')
            .val('')
            .text('-' + this.translate('Skip', 'labels', 'Import') + '-');

        const scope = this.formData.entityType;

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
                    const baseField = field.substr(0, field.length - 2);

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        label = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('id', 'fields') + ')';
                    }
                }
                else if (field.indexOf('Name') === field.length - 4) {
                    const baseField = field.substr(0, field.length - 4);

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        label = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('name', 'fields') + ')';
                    }
                }
                else if (field.indexOf('Type') === field.length - 4) {
                    const baseField = field.substr(0, field.length - 4);

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        label = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('type', 'fields') + ')';
                    }
                }
                else if (field.indexOf('phoneNumber') === 0) {
                    const phoneNumberType = field.substr(11);

                    const phoneNumberTypeLabel = this.getLanguage()
                        .translateOption(phoneNumberType, 'phoneNumber', scope);

                    label = this.translate('phoneNumber', 'fields', scope) +
                        ' (' + phoneNumberTypeLabel + ')';
                }
                else if (
                    field.indexOf('emailAddress') === 0 &&
                    parseInt(field.substr(12)).toString() === field.substr(12)
                ) {
                    const emailAddressNum = field.substr(12);

                    label = this.translate('emailAddress', 'fields', scope) + ' ' + emailAddressNum.toString();
                }
                else if (field.indexOf('Ids') === field.length - 3) {
                    const baseField = field.substr(0, field.length - 3);

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
    }

    addField(name) {
        this.$el.find('[data-action="addField"][data-name="' + name + '"]')
            .parent()
            .addClass('hidden');

        $(this.containerSelector + ' button[data-name="update"]').removeClass('disabled');

        Espo.Ui.notify(' ... ');

        let label = this.translate(name, 'fields', this.scope);
        label = this.getHelper().escapeString(label);

        const removeLink =
            '<a role="button" class="pull-right" data-action="removeField" data-name="' + name + '">' +
            '<span class="fas fa-times"></span></a>';

        const html =
            '<div class="cell form-group">' + removeLink + '<label class="control-label">' + label +
            '</label><div class="field" data-name="' + name + '"/></div>';

        $('#default-values-container').append(html);

        const type = Espo.Utils.upperCaseFirst(this.model.getFieldParam(name, 'type'));

        const viewName =
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
    }

    disableButtons() {
        this.$el.find('button[data-action="next"]').addClass('disabled').attr('disabled', 'disabled');
        this.$el.find('button[data-action="back"]').addClass('disabled').attr('disabled', 'disabled');
    }

    enableButtons() {
        this.$el.find('button[data-action="next"]').removeClass('disabled').removeAttr('disabled');
        this.$el.find('button[data-action="back"]').removeClass('disabled').removeAttr('disabled');
    }

    /**
     * @param {string} field
     * @return {module:views/fields/base}
     */
    getFieldView(field) {
        return this.getView(field);
    }

    fetch(skipValidation) {
        const attributes = {};

        this.additionalFields.forEach(field => {
            const view = this.getFieldView(field);

            _.extend(attributes, view.fetch());
        });

        this.model.set(attributes);

        let notValid = false;

        this.additionalFields.forEach(field => {
            const view = this.getFieldView(field);

            notValid = view.validate() || notValid;
        });

        if (!notValid) {
            this.formData.defaultValues = attributes;
        }

        if (notValid && !skipValidation) {
            return false;
        }

        this.formData.defaultFieldList = Espo.Utils.clone(this.additionalFields);

        const attributeList = [];

        this.mapping.forEach((d, i) => {
            attributeList.push($('#column-' + i).val());
        });

        this.formData.attributeList = attributeList;

        if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
            const updateBy = [];

            this.mapping.forEach((d, i) => {
                if ($('#update-by-' + i).get(0).checked) {
                    updateBy.push(i);
                }
            });

            this.formData.updateBy = updateBy;
        }

        this.getParentIndexView().formData = this.formData;
        this.getParentIndexView().trigger('change');

        return true;
    }

    /**
     * @return {import('./index').default}
     */
    getParentIndexView() {
        // noinspection JSValidateTypes
        return this.getParentView();
    }

    back() {
        this.fetch(true);

        this.getParentIndexView().changeStep(1);
    }

    next() {
        if (!this.fetch()) {
            return;
        }

        this.disableButtons();

        Espo.Ui.notify(' ... ');

        Espo.Ajax.postRequest('Import/file', null, {
            timeout: 0,
            contentType: 'text/csv',
            data: this.getParentIndexView().fileContents,
        }).then(result => {
            if (!result.attachmentId) {
                Espo.Ui.error(this.translate('Bad response'));

                return;
            }

            this.runImport(result.attachmentId);
        });
    }

    runImport(attachmentId) {
        this.formData.attachmentId = attachmentId;

        this.getRouter().confirmLeaveOut = false;

        Espo.Ui.notify(this.translate('importRunning', 'messages', 'Import'));

        Espo.Ajax.postRequest('Import', this.formData, {timeout: 0})
            .then(result => {
                const id = result.id;

                this.getParentIndexView().trigger('done');

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
                        '```php command.php import --id=' + id + '```',
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
    }
}

export default Step2ImportView;
