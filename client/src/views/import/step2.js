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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/import/step2', 'view', function (Dep) {

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
                var field = $(e.currentTarget).data('name');
                $(e.currentTarget).parent().addClass('hidden');
                this.addField(field);
            },

            'click a[data-action="removeField"]': function (e) {
                var field = $(e.currentTarget).data('name');

                this.$el.find('a[data-action="addField"]').parent().removeClass('hidden');

                var index = this.additionalFields.indexOf(field);
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

            var mapping = [];

            this.additionalFields = [];

            if (this.formData.previewArray) {
                var index = 0;
                if (this.formData.headerRow) {
                    index = 1;
                }
                if (this.formData.previewArray.length > index) {
                    this.formData.previewArray[index].forEach(function (value, i) {
                        var d = {
                            value: value
                        };
                        if (this.formData.headerRow) {
                            d.name = this.formData.previewArray[0][i];
                        }
                        mapping.push(d);
                    }, this);
                }
            }

            this.wait(true);
            this.getModelFactory().create(this.scope, function (model) {
                this.model = model;
                this.wait(false);
            }, this);

            this.mapping = mapping;
        },

        afterRender: function () {
            $container = $('#mapping-container');

            $table = $('<table>').addClass('table').addClass('table-bordered');

            $row = $('<tr>');
            if (this.formData.headerRow) {
                $cell = $('<th>').attr('width', '27%').html(this.translate('Header Row Value', 'labels', 'Import'));
                $row.append($cell);
            }
            $cell = $('<th>').attr('width', '33%').html(this.translate('Field', 'labels', 'Import'));
            $row.append($cell);
            $cell = $('<th>').html(this.translate('First Row Value', 'labels', 'Import'));
            $row.append($cell);

            if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
                $cell = $('<th>').html(this.translate('Update by', 'labels', 'Import'));
                $row.append($cell);
            }
            $table.append($row);

            this.mapping.forEach(function (d, i) {
                $row = $('<tr>');
                if (this.formData.headerRow) {
                    $cell = $('<td>').html(d.name);
                    $row.append($cell);
                }
                $select = this.getFieldDropdown(i, d.name);
                $cell = $('<td>').append($select);
                $row.append($cell);

                $cell = $('<td>').html(d.value);
                $row.append($cell);

                if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
                    var $checkbox = $('<input>').attr('type', 'checkbox').attr('id', 'update-by-' + i.toString());
                    if (d.name == 'id') {
                        $checkbox.attr('checked', true);
                    }
                    $cell = $('<td>').append($checkbox);
                    $row.append($cell);
                }

                $table.append($row);
            }, this);

            $container.empty();
            $container.append($table);

        },

        getFieldList: function () {
            var defs = this.getMetadata().get('entityDefs.' + this.scope + '.fields');

            var fieldList = [];
            for (var field in defs) {
                var d = defs[field];

                if (!~this.allowedFieldList.indexOf(field) && (d.readOnly || d.disabled || d.importDisabled)) {
                    continue;
                }
                fieldList.push(field);
            }

            fieldList = fieldList.sort(function (v1, v2) {
                return this.translate(v1, 'fields', this.scope).localeCompare(this.translate(v2, 'fields', this.scope));
            }.bind(this));

            return fieldList;
        },

        getAttributeList: function () {
            var fields = this.getMetadata().get('entityDefs.' + this.scope + '.fields');

            var fieldList = [];
            fieldList.push('id');

            for (var field in fields) {
                var d = fields[field];
                if (!~this.allowedFieldList.indexOf(field) && (d.readOnly || d.disabled || d.importDisabled)) {
                    continue;
                }

                if (d.type == 'phone') {
                    (this.getMetadata().get('entityDefs.' + this.scope + '.fields.' + field + '.typeList' ) || []).map(function (item) {
                        return item.replace(/\s/g, '_');
                    }, this).forEach(function (item) {
                        fieldList.push(field + Espo.Utils.upperCaseFirst(item));
                    }, this);
                    continue;
                }

                if (d.type == 'link') {
                    fieldList.push(field + 'Name');
                    fieldList.push(field + 'Id');
                }

                if (~['linkMultiple', 'foreign'].indexOf(d.type)) {
                    continue;
                }

                if (d.type == 'personName') {
                    fieldList.push(field);
                }

                var type = d.type;
                var actualFields = this.getFieldManager().getActualAttributes(type, field);
                actualFields.forEach(function (f) {
                    if (fieldList.indexOf(f) === -1) {
                        fieldList.push(f);
                    }
                }, this);
            }

            fieldList = fieldList.sort(function (v1, v2) {
                return this.translate(v1, 'fields', this.scope).localeCompare(this.translate(v2, 'fields', this.scope));
            }.bind(this));

            return fieldList
        },

        getFieldDropdown: function (num, name) {
            name = name || false;

            var fieldList = this.getAttributeList();

            $select = $('<select>').addClass('form-control').attr('id', 'column-' + num.toString());
            $option = $('<option>').val('').html('-' + this.translate('Skip', 'labels', 'Import') + '-');

            $select.append($option);
            fieldList.forEach(function (field) {
                $option = $('<option>').val(field).html(this.translate(field, 'fields', this.formData.entityType));

                if (name) {
                    if (field == name) {
                        $option.prop('selected', true);
                    } else {
                        if (name.toLowerCase().replace('_', '') == field.toLowerCase()) {
                            $option.prop('selected', true);
                        }
                    }
                }
                $select.append($option);
            }, this);

            return $select;
        },

        addField: function (name) {
            $(this.containerSelector + ' button[data-name="update"]').removeClass('disabled');

            this.notify('Loading...');
            var label = this.translate(name, 'fields', this.scope);

            var removeLink = '<a href="javascript:" class="pull-right" data-action="removeField" data-name="'+name+'"><span class="glyphicon glyphicon-remove"></span></a>';

            var html = '<div class="cell form-group col-sm-3">'+removeLink+'<label class="control-label">' + label + '</label><div class="field" data-name="'+name+'"/></div>';
            $('#default-values-container').append(html);

            var type = Espo.Utils.upperCaseFirst(this.model.getFieldParam(name, 'type'));
            this.createView(name, this.getFieldManager().getViewName(type), {
                model: this.model,
                el: this.getSelector() + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                },
                mode: 'edit'
            }, function (view) {
                this.additionalFields.push(name);
                view.render();
                view.notify(false);
            }.bind(this));
        },

        disableButtons: function () {
            this.$el.find('button[data-action="next"]').addClass('disabled');
            this.$el.find('button[data-action="back"]').addClass('disabled');
        },

        enableButtons: function () {
            this.$el.find('button[data-action="next"]').removeClass('disabled');
            this.$el.find('button[data-action="back"]').removeClass('disabled');
        },

        fetch: function () {

            var attributes = {};
            this.additionalFields.forEach(function (field) {
                var view = this.getView(field);
                _.extend(attributes, view.fetch());
            }, this);

            this.model.set(attributes);

            var notValid = false;
            this.additionalFields.forEach(function (field) {
                var view = this.getView(field);
                notValid = view.validate() || notValid;
            }, this);

            if (!notValid) {
                this.formData.defaultValues = attributes;
                return true;
            }
        },

        back: function () {
            this.getParentView().changeStep(1);
        },


        next: function () {
            if (!this.fetch()) {
                return;
            }

            var fields = [];

            this.mapping.forEach(function (d, i) {
                fields.push($('#column-' + i).val());
            }, this);

            this.formData.fields = fields;

            if (~['update', 'createAndUpdate'].indexOf(this.formData.action)) {
                var updateBy = [];
                this.mapping.forEach(function (d, i) {
                    if ($('#update-by-' + i).get(0).checked) {
                        updateBy.push(i);
                    }
                }, this);
                this.formData.updateBy = updateBy;
            }


            this.getParentView().formData = this.formData;

            this.disableButtons();

            this.notify('File uploading...');

            $.ajax({
                type: 'POST',
                url: 'Import/action/uploadFile',
                contentType: 'text/csv',
                data: this.getParentView().fileContents,
                timeout: 0,
                success: function (data) {
                    if (data.attachmentId) {
                        this.runImport(data.attachmentId);
                    } else {
                        this.notify('Bad response', 'error');
                    }
                }.bind(this)
            });
        },

        runImport: function (attachmentId) {
            this.formData.attachmentId = attachmentId;

            this.notify('Import running...');

            $.ajax({
                type: 'POST',
                url: 'Import',
                data: JSON.stringify(this.formData),
                timeout: 0,
                success: function (result) {
                    var id = result.id;
                    if (id) {
                        this.getRouter().navigate('#Import/view/' + id, {trigger: true});
                    } else {
                        this.notify('Error', 'error');
                        this.enableButtons();
                    }
                    this.notify(false);
                }.bind(this),
                error: function () {
                    this.enableButtons();
                }.bind(this),
            });
        }

    });
});
