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

define('views/import/step1', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'import/step-1',

        events: {
            'change #import-file': function (e) {
                var $file = $(e.currentTarget);
                var files = e.currentTarget.files;
                if (files.length) {
                    this.loadFile(files[0]);
                }
            },

            'click button[data-action="next"]': function () {
                this.next();
            },

            'click button[data-action="saveAsDefault"]': function () {
                this.saveAsDefault();
            },
        },

        getEntityList: function () {
            var list = [];
            var scopes = this.getMetadata().get('scopes');
            for (var scopeName in scopes) {
                if (scopes[scopeName].importable) {
                    if (!this.getAcl().checkScope(scopeName, 'create')) continue;
                    list.push(scopeName);
                }
            }
            list.sort(function (v1, v2) {
                 return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
            }.bind(this));

            return list;
        },

        data: function () {
            return {
                entityList: this.getEntityList(),
            };
        },

        setup: function () {
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
            ];

            this.paramList.forEach(function (item) {
                this.attributeList.push(item);
            }, this);

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

            var defaults = Espo.Utils.cloneDeep(
                (this.getPreferences().get('importParams') || {}).default || {}
            );

            if (!this.options.formData) {
                for (var p in defaults) {
                    this.formData[p] = defaults[p];
                }
            }

            var model = this.model = new Model;

            this.attributeList.forEach(function (a) {
                model.set(a, this.formData[a]);
            }, this);

            this.attributeList.forEach(function (a) {
                this.listenTo(model, 'change:' + a, function (m, v, o) {
                    if (!o.ui) return;
                    this.formData[a] = this.model.get(a);
                    this.preview();
                }, this);
            }, this);

            var personNameFormatList = [
                'f l',
                'l f',
                'l, f',
            ];
            var personNameFormat = this.getConfig().get('personNameFormat') || 'firstLast';
            if (~personNameFormat.toString().toLowerCase().indexOf('middle')) {
                personNameFormatList.push('f m l');
                personNameFormatList.push('l f m');
            }

            var dateFormatDataList = this.getDateFormatDataList();

            var timeFormatDataList = this.getTimeFormatDataList();

            var dateFormatList = [];
            var dateFormatOptions = {};

            dateFormatDataList.forEach(function (item) {
                dateFormatList.push(item.key);
                dateFormatOptions[item.key] = item.label;
            }, this);

            var timeFormatList = [];
            var timeFormatOptions = {};

            timeFormatDataList.forEach(function (item) {
                timeFormatList.push(item.key);
                timeFormatOptions[item.key] = item.label;
            }, this);

            this.createView('actionField', 'views/fields/enum', {
                el: this.getSelector() + ' .field[data-name="action"]',
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
                        create: this.translate('Create Only', 'labels', 'Admin'),
                        createAndUpdate: this.translate('Create and Update', 'labels', 'Admin'),
                        update: this.translate('Update Only', 'labels', 'Admin'),
                    },
                },
            });

            this.createView('entityTypeField', 'views/fields/enum', {
                el: this.getSelector() + ' .field[data-name="entityType"]',
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
                el: this.getSelector() + ' .field[data-name="decimalMark"]',
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
                el: this.getSelector() + ' .field[data-name="personNameFormat"]',
                model: this.model,
                name: 'personNameFormat',
                mode: 'edit',
                params: {
                    options: personNameFormatList,
                    translation: 'Import.options.personNameFormat',
                },
            });
            this.createView('delimiterField', 'views/fields/enum', {
                el: this.getSelector() + ' .field[data-name="delimiter"]',
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
                el: this.getSelector() + ' .field[data-name="textQualifier"]',
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
                el: this.getSelector() + ' .field[data-name="dateFormat"]',
                model: this.model,
                name: 'dateFormat',
                mode: 'edit',
                params: {
                    options: dateFormatList,
                    translatedOptions: dateFormatOptions,
                },
            });
            this.createView('timeFormatField', 'views/fields/enum', {
                el: this.getSelector() + ' .field[data-name="timeFormat"]',
                model: this.model,
                name: 'timeFormat',
                mode: 'edit',
                params: {
                    options: timeFormatList,
                    translatedOptions: timeFormatOptions,
                },
            });
            this.createView('currencyField', 'views/fields/enum', {
                el: this.getSelector() + ' .field[data-name="currency"]',
                model: this.model,
                name: 'currency',
                mode: 'edit',
                params: {
                    options: this.getConfig().get('currencyList'),
                },
            });
            this.createView('timezoneField', 'views/fields/enum', {
                el: this.getSelector() + ' .field[data-name="timezone"]',
                model: this.model,
                name: 'timezone',
                mode: 'edit',
                params: {
                    options: this.getMetadata().get(['entityDefs', 'Settings', 'fields', 'timeZone', 'options']),
                },
            });
            this.createView('headerRowField', 'views/fields/bool', {
                el: this.getSelector() + ' .field[data-name="headerRow"]',
                model: this.model,
                name: 'headerRow',
                mode: 'edit',
            });
            this.createView('silentModeField', 'views/fields/bool', {
                el: this.getSelector() + ' .field[data-name="silentMode"]',
                model: this.model,
                name: 'silentMode',
                mode: 'edit',
                tooltip: true,
                tooltipText: this.translate('silentMode', 'tooltips', 'Import'),
            });
            this.createView('idleModeField', 'views/fields/bool', {
                el: this.getSelector() + ' .field[data-name="idleMode"]',
                model: this.model,
                name: 'idleMode',
                mode: 'edit',
            });
            this.createView('skipDuplicateCheckingField', 'views/fields/bool', {
                el: this.getSelector() + ' .field[data-name="skipDuplicateChecking"]',
                model: this.model,
                name: 'skipDuplicateChecking',
                mode: 'edit',
            });
            this.createView('manualModeField', 'views/fields/bool', {
                el: this.getSelector() + ' .field[data-name="manualMode"]',
                model: this.model,
                name: 'manualMode',
                mode: 'edit',
                tooltip: true,
                tooltipText: this.translate('manualMode', 'tooltips', 'Import'),
            });

            this.listenTo(this.model, 'change', function (m, o) {
                if (!o.ui) return;

                var isParamChanged = false;
                this.paramList.forEach(function (a) {
                    if (m.hasChanged(a)) {
                        isParamChanged = true;
                    }
                }, this);

                if (isParamChanged) {
                    this.showSaveAsDefaultButton();
                }
            }, this);

            this.listenTo(this.model, 'change', function (m, o) {
                if (this.isRendered()) {
                    this.controlFieldVisibility();
                }
            }, this);

            this.listenTo(this.model, 'change:entityType', function () {
                delete this.formData.defaultFieldList;
                delete this.formData.defaultValues;
                delete this.formData.attributeList;
                delete this.formData.updateBy;
            }, this);

            this.listenTo(this.model, 'change:action', function () {
                delete this.formData.updateBy;
            }, this);

            this.listenTo(this.model, 'change', function (m, o) {
                if (!o.ui) return;

                this.getRouter().confirmLeaveOut = true;
            }, this);
        },

        afterRender: function () {
            this.setupFormData();
            if (this.getParentView() && this.getParentView().fileContents) {
                this.setFileIsLoaded();
                this.preview();
            }
            this.controlFieldVisibility();
        },

        showSaveAsDefaultButton: function () {
            this.$el.find('[data-action="saveAsDefault"]').removeClass('hidden');
        },

        hideSaveAsDefaultButton: function () {
            this.$el.find('[data-action="saveAsDefault"]').addClass('hidden');
        },

        next: function () {
            this.attributeList.forEach(function (a) {
                this.getView(a + 'Field').fetchToModel();
                this.formData[a] = this.model.get(a);
            }, this);

            var isInvalid = false;
            this.attributeList.forEach(function (a) {
                isInvalid |= this.getView(a + 'Field').validate();
            }, this);

            if (isInvalid) {
                Espo.Ui.error(this.translate('Not valid'));
                return;
            }

            this.getParentView().formData = this.formData;

            this.getParentView().trigger('change');

            this.getParentView().changeStep(2);
        },

        setupFormData: function () {
            this.attributeList.forEach(function (a) {
                this.model.set(a, this.formData[a]);
            }, this);
        },

        loadFile: function (file) {
            var blob = file.slice(0, 1024 * 16);

            var readerPreview = new FileReader();
            readerPreview.onloadend = function (e) {
                if (e.target.readyState == FileReader.DONE) {
                    this.formData.previewString = e.target.result;
                    this.preview();
                }
            }.bind(this);
            readerPreview.readAsText(blob);

            var reader = new FileReader();
            reader.onloadend = function (e) {
                if (e.target.readyState == FileReader.DONE) {
                    this.getParentView().fileContents = e.target.result;
                    this.setFileIsLoaded();

                    this.getRouter().confirmLeaveOut = true;
                }
            }.bind(this);
            reader.readAsText(file);
        },

        setFileIsLoaded: function () {
            this.$el.find('button[data-action="next"]').removeClass('hidden');
        },

        preview: function () {
            if (!this.formData.previewString) {
                return;
            }
            var arr = this.csvToArray(this.formData.previewString, this.formData.delimiter, this.formData.textQualifier);

            this.formData.previewArray = arr;

            var $table = $('<table>').addClass('table').addClass('table-bordered');

            var $tbody = $('<tbody>').appendTo($table);

            arr.forEach((row, i) => {
                if (i >= 3) {
                    return;
                }

                $row = $('<tr>');

                row.forEach((value) => {
                    $cell = $('<td>').html(value);
                    $row.append($cell);
                });

                $tbody.append($row);
            });

            var $container = $('#import-preview');

            $container.empty().append($table);
        },

        csvToArray: function (strData, strDelimiter, strQualifier) {
            strDelimiter = (strDelimiter || ',');
            strQualifier = (strQualifier || '\"');

            strDelimiter = strDelimiter.replace(/\\t/, '\t');

            var objPattern = new RegExp(
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

            var arrData = [[]];
            var arrMatches = null;

            while (arrMatches = objPattern.exec(strData)) {
                var strMatchedDelimiter = arrMatches[1];

                if (
                    strMatchedDelimiter.length &&
                    (strMatchedDelimiter != strDelimiter)
                    ) {
                    arrData.push([]);
                }

                if (arrMatches[2]) {
                    var strMatchedValue = arrMatches[2].replace(
                            new RegExp( "\"\"", "g" ),
                            "\""
                        );
                } else {
                    var strMatchedValue = arrMatches[3];
                }

                arrData[arrData.length - 1].push(strMatchedValue);
            }

            return arrData;
        },

        saveAsDefault: function () {
            var preferences = this.getPreferences();

            var importParams = Espo.Utils.cloneDeep(preferences.get('importParams') || {});

            var data = {};

            this.paramList.forEach(function (a) {
                data[a] = this.model.get(a);
            }, this);

            importParams.default = data;

            preferences.save({
                'importParams': importParams,
            }).then(
                function () {
                    Espo.Ui.success(this.translate('Saved'))
                }.bind(this)
            );

            this.hideSaveAsDefaultButton();
        },

        controlFieldVisibility: function () {
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
        },

        hideField: function (name) {
            this.$el.find('.field[data-name="'+name+'"]').parent().addClass('hidden-cell');
        },

        showField: function (name) {
            this.$el.find('.field[data-name="'+name+'"]').parent().removeClass('hidden-cell');
        },

        convertFormatToLabel: function (format) {
            var formatItemLabelMap = {
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

            var label = format;

            for (let item in formatItemLabelMap) {
                var value = formatItemLabelMap[item];

                label = label.replace(new RegExp(item, 'g'), value);
            }

            return format + ' - ' + label;
        },

        getDateFormatDataList: function () {
            var dateFormatList = this.getMetadata().get(['clientDefs', 'Import', 'dateFormatList']) || [];

            return dateFormatList.map(
                function (item) {
                    return {
                        key: item,
                        label: this.convertFormatToLabel(item),
                    };
                }.bind(this)
            );
        },

        getTimeFormatDataList: function () {
            var timeFormatList = this.getMetadata().get(['clientDefs', 'Import', 'timeFormatList']) || [];

            return timeFormatList.map(
                function (item) {
                    return {
                        key: item,
                        label: this.convertFormatToLabel(item),
                    };
                }.bind(this)
            );
        },

    });
});
