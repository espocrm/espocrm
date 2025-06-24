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

import View from 'view';
import Model from 'model';
// noinspection NpmUsedModulesInstalled
import intlTelInputGlobals from 'intl-tel-input-globals';

class Step1ImportView extends View {

    template = 'import/step-1'

    events = {
        /** @this Step1ImportView */
        'change #import-file': function (e) {
            const files = e.currentTarget.files;

            if (files.length) {
                this.loadFile(files[0]);
            }
        },
        /** @this Step1ImportView */
        'click button[data-action="next"]': function () {
            this.next();
        },
        /** @this Step1ImportView */
        'click button[data-action="saveAsDefault"]': function () {
            this.saveAsDefault();
        },
    }

    getEntityList() {
        const list = [];
        /** @type {Object.<string, Record>} */
        const scopes = this.getMetadata().get('scopes');

        for (const scopeName in scopes) {
            if (scopes[scopeName].importable) {
                if (!this.getAcl().checkScope(scopeName, 'create')) {
                    continue;
                }

                list.push(scopeName);
            }
        }

        list.sort((v1, v2) => {
             return this.translate(v1, 'scopeNamesPlural')
                 .localeCompare(this.translate(v2, 'scopeNamesPlural'));
        });

        return list;
    }

    data() {
        return {
            entityList: this.getEntityList(),
        };
    }

    setup() {
        this.attributeList = [
            'entityType',
            'action',
        ];

        this.paramList = [
            'headerRow',
            'decimalMark',
            'personNameFormat',
            'delimiter',
            'dateFormat',
            'timeFormat',
            'currency',
            'timezone',
            'textQualifier',
            'silentMode',
            'idleMode',
            'skipDuplicateChecking',
            'manualMode',
            'phoneNumberCountry',
        ];

        this.paramList.forEach(item => {
            this.attributeList.push(item);
        });

        this.formData = this.options.formData || {
            entityType: this.options.entityType || null,
            create: 'create',
            headerRow: true,
            delimiter: ',',
            textQualifier: '"',
            dateFormat: 'YYYY-MM-DD',
            timeFormat: 'HH:mm:ss',
            currency: this.getConfig().get('defaultCurrency'),
            timezone: 'UTC',
            decimalMark: '.',
            personNameFormat: 'f l',
            idleMode: false,
            skipDuplicateChecking: false,
            silentMode: true,
            manualMode: false,
        };

        const defaults = Espo.Utils.cloneDeep(
            (this.getPreferences().get('importParams') || {}).default || {});

        if (!this.options.formData) {
            for (const p in defaults) {
                this.formData[p] = defaults[p];
            }
        }

        const model = this.model = new Model;

        this.attributeList.forEach(a => {
            model.set(a, this.formData[a]);
        });

        this.attributeList.forEach(a => {
            this.listenTo(model, 'change:' + a, (m, v, o) => {
                if (!o.ui) {
                    return;
                }

                this.formData[a] = this.model.get(a);

                this.preview();
            });
        });

        const personNameFormatList = [
            'f l',
            'l f',
            'l, f',
        ];

        const personNameFormat = this.getConfig().get('personNameFormat') || 'firstLast';

        if (~personNameFormat.toString().toLowerCase().indexOf('middle')) {
            personNameFormatList.push('f m l');
            personNameFormatList.push('l f m');
        }

        const dateFormatDataList = this.getDateFormatDataList();
        const timeFormatDataList = this.getTimeFormatDataList();

        const dateFormatList = [];
        const dateFormatOptions = {};

        dateFormatDataList.forEach(item => {
            dateFormatList.push(item.key);

            dateFormatOptions[item.key] = item.label;
        });

        const timeFormatList = [];
        const timeFormatOptions = {};

        timeFormatDataList.forEach(item => {
            timeFormatList.push(item.key);

            timeFormatOptions[item.key] = item.label;
        });

        this.createView('actionField', 'views/fields/enum', {
            selector: '.field[data-name="action"]',
            model: this.model,
            name: 'action',
            mode: 'edit',
            params: {
                options: [
                    'create',
                    'createAndUpdate',
                    'update',
                ],
                translatedOptions: {
                    create: this.translate('Create Only', 'labels', 'Import'),
                    createAndUpdate: this.translate('Create and Update', 'labels', 'Import'),
                    update: this.translate('Update Only', 'labels', 'Import'),
                },
            },
        });

        this.createView('entityTypeField', 'views/fields/enum', {
            selector: '.field[data-name="entityType"]',
            model: this.model,
            name: 'entityType',
            mode: 'edit',
            params: {
                options: [''].concat(this.getEntityList()),
                translation: 'Global.scopeNamesPlural',
                required: true,
            },
            labelText: this.translate('Entity Type', 'labels', 'Import'),
        });

        this.createView('decimalMarkField', 'views/fields/varchar', {
            selector: '.field[data-name="decimalMark"]',
            model: this.model,
            name: 'decimalMark',
            mode: 'edit',
            params: {
                options: [
                    '.',
                    ',',
                ],
                maxLength: 1,
                required: true,
            },
            labelText: this.translate('Decimal Mark', 'labels', 'Import'),
        });

        this.createView('personNameFormatField', 'views/fields/enum', {
            selector: '.field[data-name="personNameFormat"]',
            model: this.model,
            name: 'personNameFormat',
            mode: 'edit',
            params: {
                options: personNameFormatList,
                translation: 'Import.options.personNameFormat',
            },
        });

        this.createView('delimiterField', 'views/fields/enum', {
            selector: '.field[data-name="delimiter"]',
            model: this.model,
            name: 'delimiter',
            mode: 'edit',
            params: {
                options: [
                    ',',
                    ';',
                    '\\t',
                    '|',
                ],
            },
        });

        this.createView('textQualifierField', 'views/fields/enum', {
            selector: '.field[data-name="textQualifier"]',
            model: this.model,
            name: 'textQualifier',
            mode: 'edit',
            params: {
                options: ['"', '\''],
                translatedOptions: {
                    '"': this.translate('Double Quote', 'labels', 'Import'),
                    '\'': this.translate('Single Quote', 'labels', 'Import'),
                },
            },
        });

        this.createView('dateFormatField', 'views/fields/enum', {
            selector: '.field[data-name="dateFormat"]',
            model: this.model,
            name: 'dateFormat',
            mode: 'edit',
            params: {
                options: dateFormatList,
                translatedOptions: dateFormatOptions,
            },
        });

        this.createView('timeFormatField', 'views/fields/enum', {
            selector: '.field[data-name="timeFormat"]',
            model: this.model,
            name: 'timeFormat',
            mode: 'edit',
            params: {
                options: timeFormatList,
                translatedOptions: timeFormatOptions,
            },
        });

        this.createView('currencyField', 'views/fields/enum', {
            selector: '.field[data-name="currency"]',
            model: this.model,
            name: 'currency',
            mode: 'edit',
            params: {
                options: this.getConfig().get('currencyList'),
            },
        });

        this.createView('timezoneField', 'views/fields/enum', {
            selector: '.field[data-name="timezone"]',
            model: this.model,
            name: 'timezone',
            mode: 'edit',
            params: {
                options:  Espo.Utils.clone(this.getHelper().getAppParam('timeZoneList')) || [],
            },
        });

        this.createView('headerRowField', 'views/fields/bool', {
            selector: '.field[data-name="headerRow"]',
            model: this.model,
            name: 'headerRow',
            mode: 'edit',
        });

        this.createView('silentModeField', 'views/fields/bool', {
            selector: '.field[data-name="silentMode"]',
            model: this.model,
            name: 'silentMode',
            mode: 'edit',
            tooltip: true,
            tooltipText: this.translate('silentMode', 'tooltips', 'Import'),
        });

        this.createView('idleModeField', 'views/fields/bool', {
            selector: '.field[data-name="idleMode"]',
            model: this.model,
            name: 'idleMode',
            mode: 'edit',
        });

        this.createView('skipDuplicateCheckingField', 'views/fields/bool', {
            selector: '.field[data-name="skipDuplicateChecking"]',
            model: this.model,
            name: 'skipDuplicateChecking',
            mode: 'edit',
        });

        this.createView('manualModeField', 'views/fields/bool', {
            selector: '.field[data-name="manualMode"]',
            model: this.model,
            name: 'manualMode',
            mode: 'edit',
            tooltip: true,
            tooltipText: this.translate('manualMode', 'tooltips', 'Import'),
        });

        this.createView('phoneNumberCountryField', 'views/fields/enum', {
            selector: '.field[data-name="phoneNumberCountry"]',
            model: this.model,
            name: 'phoneNumberCountry',
            mode: 'edit',
            params: {
                options: ['', ...intlTelInputGlobals.getCountryData().map(item => item.iso2)],
            },
            translatedOptions: intlTelInputGlobals.getCountryData()
                .reduce((map, item) => {
                    map[item.iso2] = `${item.iso2.toUpperCase()} +${item.dialCode}`;

                    return map;
                }, {})
        });

        this.listenTo(this.model, 'change', (m, o) => {
            if (!o.ui) {
                return;
            }

            let isParamChanged = false;

            this.paramList.forEach(a => {
                if (m.hasChanged(a)) {
                    isParamChanged = true;
                }
            });

            if (isParamChanged) {
                this.showSaveAsDefaultButton();
            }
        });

        this.listenTo(this.model, 'change', () => {
            if (this.isRendered()) {
                this.controlFieldVisibility();
            }
        });

        this.listenTo(this.model, 'change:entityType', () => {
            delete this.formData.defaultFieldList;
            delete this.formData.defaultValues;
            delete this.formData.attributeList;
            delete this.formData.updateBy;
        });

        this.listenTo(this.model, 'change:action', () => {
            delete this.formData.updateBy;
        });

        this.listenTo(this.model, 'change', (m, o) => {
            if (!o.ui) {
                return;
            }

            this.getRouter().confirmLeaveOut = true;
        });
    }

    afterRender() {
        this.setupFormData();

        if (this.getParentIndexView() && this.getParentIndexView().fileContents) {
            this.setFileIsLoaded();
            this.preview();
        }

        this.controlFieldVisibility();
    }

    /**
     * @return {import('./index').default}
     */
    getParentIndexView() {
        // noinspection JSValidateTypes
        return this.getParentView();
    }

    showSaveAsDefaultButton() {
        this.$el.find('[data-action="saveAsDefault"]').removeClass('hidden');
    }

    hideSaveAsDefaultButton() {
        this.$el.find('[data-action="saveAsDefault"]').addClass('hidden');
    }

    /**
     * @return {module:views/fields/base}
     */
    getFieldView(field) {
        return this.getView(field + 'Field');
    }

    next() {
        this.attributeList.forEach(field => {
            this.getFieldView(field).fetchToModel();

            this.formData[field] = this.model.get(field);
        });

        let isInvalid = false;

        this.attributeList.forEach(field => {
            isInvalid |= this.getFieldView(field).validate();
        });

        if (isInvalid) {
            Espo.Ui.error(this.translate('Not valid'));

            return;
        }

        this.getParentIndexView().formData = this.formData;
        this.getParentIndexView().trigger('change');
        this.getParentIndexView().changeStep(2);
    }

    setupFormData() {
        this.attributeList.forEach(field => {
            this.model.set(field, this.formData[field]);
        });
    }

    /**
     * @param {File} file
     */
    loadFile(file) {
        const blob = file.slice(0, 1024 * 512);

        const readerPreview = new FileReader();

        readerPreview.onloadend = e => {
            if (e.target.readyState === FileReader.DONE) {
                this.formData.previewString = e.target.result;

                this.preview();
            }
        };

        readerPreview.readAsText(blob);

        const reader = new FileReader();

        reader.onloadend = e => {
            if (e.target.readyState === FileReader.DONE) {
                this.getParentIndexView().fileContents = e.target.result;

                this.setFileIsLoaded();
                this.getRouter().confirmLeaveOut = true;
                this.setFileName(file.name);
            }
        };

        reader.readAsText(file);
    }

    /**
     * @param {string} name
     */
    setFileName(name) {
        this.$el.find('.import-file-name').text(name);
        this.$el.find('.import-file-info').text('');
    }

    setFileIsLoaded() {
        this.$el.find('button[data-action="next"]').removeClass('hidden');
    }

    preview() {
        if (!this.formData.previewString) {
            return;
        }

        const arr = this.csvToArray(
            this.formData.previewString,
            this.formData.delimiter,
            this.formData.textQualifier
        );

        this.formData.previewArray = arr;

        const $table = $('<table>').addClass('table').addClass('table-bordered');
        const $tbody = $('<tbody>').appendTo($table);

        arr.forEach((row, i) => {
            if (i >= 3) {
                return;
            }

            const $row = $('<tr>');

            row.forEach((value) => {
                const $cell = $('<td>').html(this.getHelper().sanitizeHtml(value));

                $row.append($cell);
            });

            $tbody.append($row);
        });

        const $container = $('#import-preview');

        $container.empty().append($table);
    }

    csvToArray(strData, strDelimiter, strQualifier) {
        strDelimiter = (strDelimiter || ',');
        strQualifier = (strQualifier || '\"');

        strDelimiter = strDelimiter.replace(/\\t/, '\t');

        const objPattern = new RegExp(
            (
                // Delimiters.
                "(\\" + strDelimiter + "|\\r?\\n|\\r|^)" +

                // Quoted fields.
                "(?:"+strQualifier+"([^"+strQualifier+"]*(?:"+strQualifier+""+strQualifier+
                    "[^"+strQualifier+"]*)*)"+strQualifier+"|" +

                // Standard fields.
                "([^"+strQualifier+"\\" + strDelimiter + "\\r\\n]*))"
            ),
            "gi"
        );

        const arrData = [[]];
        let arrMatches = null;

        while (arrMatches = objPattern.exec(strData)) {
            const strMatchedDelimiter = arrMatches[1];
            let strMatchedValue;

            if (
                strMatchedDelimiter.length &&
                (strMatchedDelimiter !== strDelimiter)
            ) {
                arrData.push([]);
            }

            strMatchedValue = arrMatches[2] ?
                arrMatches[2].replace(new RegExp("\"\"", "g"), "\"") :
                arrMatches[3];

            arrData[arrData.length - 1].push(strMatchedValue);
        }

        return arrData;
    }

    saveAsDefault() {
        const preferences = this.getPreferences();

        const importParams = Espo.Utils.cloneDeep(preferences.get('importParams') || {});

        const data = {};

        this.paramList.forEach(attribute => {
            data[attribute] = this.model.get(attribute);
        });

        importParams.default = data;

        preferences.save({importParams: importParams})
            .then(() => {
                Espo.Ui.success(this.translate('Saved'))
            });

        this.hideSaveAsDefaultButton();
    }

    controlFieldVisibility() {
        if (this.model.get('idleMode')) {
            this.hideField('manualMode');
        } else {
            this.showField('manualMode');
        }

        if (this.model.get('manualMode')) {
            this.hideField('idleMode');
        } else {
            this.showField('idleMode');
        }
    }

    hideField(name) {
        this.$el.find('.field[data-name="'+name+'"]').parent().addClass('hidden-cell');
    }

    showField(name) {
        this.$el.find('.field[data-name="'+name+'"]').parent().removeClass('hidden-cell');
    }

    convertFormatToLabel(format) {
        const formatItemLabelMap = {
            'YYYY': '2021',
            'DD': '27',
            'MM': '12',
            'HH': '23',
            'mm': '00',
            'hh': '11',
            'ss': '00',
            'a': 'pm',
            'A': 'PM',
        };

        let label = format;

        for (const item in formatItemLabelMap) {
            const value = formatItemLabelMap[item];

            label = label.replace(new RegExp(item, 'g'), value);
        }

        return format + ' · ' + label;
    }

    getDateFormatDataList() {
        const dateFormatList = this.getMetadata().get(['clientDefs', 'Import', 'dateFormatList']) || [];

        return dateFormatList.map(item => {
            return {
                key: item,
                label: this.convertFormatToLabel(item),
            };
        });
    }

    getTimeFormatDataList() {
        const timeFormatList = this.getMetadata().get(['clientDefs', 'Import', 'timeFormatList']) || [];

        return timeFormatList.map(item => {
            return {
                key: item,
                label: this.convertFormatToLabel(item),
            };
        });
    }
}

export default Step1ImportView;
