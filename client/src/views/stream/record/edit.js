/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/stream/record/edit', 'views/record/base', function (Dep) {

    return Dep.extend({

        template: 'stream/record/edit',

        postingMode: false,

        dependencyDefs: {
            'targetType': {
                map: {
                    'users': [
                        {
                            action: 'hide',
                            fields: ['teams', 'portals']
                        },
                        {
                            action: 'show',
                            fields: ['users']
                        },
                        {
                            action: 'setNotRequired',
                            fields: ['teams', 'portals']
                        },
                        {
                            action: 'setRequired',
                            fields: ['users']
                        }
                    ],
                    'teams': [
                        {
                            action: 'hide',
                            fields: ['users', 'portals']
                        },
                        {
                            action: 'show',
                            fields: ['teams']
                        },
                        {
                            action: 'setRequired',
                            fields: ['teams']
                        },
                        {
                            action: 'setNotRequired',
                            fields: ['users', 'portals']
                        }
                    ],
                    'portals': [
                        {
                            action: 'hide',
                            fields: ['users', 'teams']
                        },
                        {
                            action: 'show',
                            fields: ['portals']
                        },
                        {
                            action: 'setRequired',
                            fields: ['portals']
                        },
                        {
                            action: 'setNotRequired',
                            fields: ['users', 'teams']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['teams', 'users', 'portals']
                    },
                    {
                        action: 'setNotRequired',
                        fields: ['teams', 'users', 'portals']
                    }
                ]
            }
        },

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.interactiveMode = this.options.interactiveMode;
            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.seed = this.model.clone();

            if (this.options.interactiveMode) {
                this.events['focus textarea[data-name="post"]'] = function (e) {
                    this.enablePostingMode();
                };
                this.events['keypress textarea[data-name="post"]'] = function (e) {
                    if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey) {
                        this.post();
                    } else if (e.keyCode == 9) {
                        $text = $(e.currentTarget);
                        if ($text.val() == '') {
                            this.disablePostingMode();
                        }
                    }
                };
                this.events['click button.post'] = function (e) {
                    this.post();
                };
            }

            var optionList = ['self'];

            this.model.set('type', 'Post');
            this.model.set('targetType', 'self');

            var assignmentPermission = this.getAcl().get('assignmentPermission');
            var portalPermission = this.getAcl().get('portalPermission');

            if (assignmentPermission === 'team' || assignmentPermission === 'all') {
                optionList.push('users');
                optionList.push('teams');
            }
            if (assignmentPermission === 'all') {
                optionList.push('all');
            }
            if (portalPermission === 'yes') {
                optionList.push('portals');
            }

            this.createField('targetType', 'views/fields/enum', {
                options: optionList
            });

            this.createField('users', 'views/fields/users', {});
            this.createField('teams', 'views/fields/teams', {});
            this.createField('portals', 'views/fields/link-multiple', {});
            this.createField('post', 'views/note/fields/post', {required: true, rows: 1});
            this.createField('attachments', 'views/stream/fields/attachment-multiple', {});

            this.listenTo(this.model, 'change', function () {
                if (this.postingMode) {
                    this.setConfirmLeaveOut(true);
                }
            }, this);
        },

        disablePostingMode: function () {
            this.postingMode = false;
            this.$el.find('.post-control').addClass('hidden');
            this.setConfirmLeaveOut(false);
            $('body').off('click.stream-create-post');

            this.getFieldView('post').$element.prop('rows', 1);
        },

        enablePostingMode: function () {
            this.$el.find('.post-control').removeClass('hidden');
            if (!this.postingMode) {
                $('body').off('click.stream-create-post');
                $('body').on('click.stream-create-post', function (e) {
                    if ($.contains(window.document.body, e.target) && !$.contains(this.$el.get(0), e.target) && !$(e.target).closest('.modal-dialog').length) {
                        if (this.getFieldView('post') && this.getFieldView('post').$element.val() == '') {
                            if (!(this.model.get('attachmentsIds') || []).length) {
                                this.disablePostingMode();
                            }
                        }
                    }
                }.bind(this));
            }

            this.postingMode = true;
        },

        afterRender: function () {
            this.$post = this.$el.find('button.post');

            var postView = this.getFieldView('post');
            if (postView) {
                this.listenTo(postView, 'add-files', function (files) {
                    this.enablePostingMode();
                    var attachmentsView = this.getFieldView('attachments');
                    if (!attachmentsView) return;
                    attachmentsView.uploadFiles(files);
                }, this);
            }
        },

        validate: function () {
            var notValid = Dep.prototype.validate.call(this);

            if (this.model.get('post') === '' && !(this.model.get('attachmentsIds') || []).length) {
                notValid = true;
            }
            return notValid;
        },

        post: function () {
            this.save();
        },

        beforeSave: function () {
            Espo.Ui.notify(this.translate('posting', 'messages'));
            this.$post.addClass('disabled');
        },

        afterSave: function () {
            Espo.Ui.success(this.translate('Posted'));
            if (this.options.interactiveMode) {
                this.model.clear();
                this.model.set('targetType', 'self');
                this.model.set('type', 'Post');

                this.disablePostingMode();
                this.$post.removeClass('disabled');
                this.getFieldView('post').$element.prop('rows', 1);
            }
        },

        afterNotValid: function () {
            this.$post.removeClass('disabled');
        }

    });

});