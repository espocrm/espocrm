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
module.exports = function (grunt) {

    var jsFilesToMinify = [
        'client/lib/jquery-2.1.4.min.js',
        'client/lib/underscore-min.js',
        'client/lib/backbone-min.js',
        'client/lib/handlebars.js',
        'client/lib/base64.js',
        'client/lib/jquery-ui.min.js',
        'client/lib/moment.min.js',
        'client/lib/moment-timezone-with-data.min.js',
        'client/lib/jquery.timepicker.min.js',
        'client/lib/jquery.autocomplete.js',
        'client/lib/bootstrap.min.js',
        'client/lib/bootstrap-datepicker.js',
        'client/lib/bull.min.js',
        'client/src/namespace.js',
        'client/src/exceptions.js',
        'client/src/loader.js',
        'client/src/utils.js'
    ];

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        mkdir: {
            tmp: {
                options: {
                    mode: 0775,
                    create: [
                        'build/tmp',
                    ]
                },

            }
        },
        clean: {
            start: ['build/*'],
            final: ['build/tmp'],
        },
        less: {
            espo: {
                options: {
                    yuicompress: true,
                },
                files: {
                    'frontend/client/css/espo.css': 'frontend/less/espo/main.less',
                },
            },
            sakura: {
                options: {
                    yuicompress: true,
                },
                files: {
                    'frontend/client/css/sakura.css': 'frontend/less/sakura/main.less',
                },
            },
            greencoffee: {
                options: {
                    yuicompress: true,
                },
                files: {
                    'frontend/client/css/greencoffee.css': 'frontend/less/greencoffee/main.less',
                },
            },
            espoVertical: {
                options: {
                    yuicompress: true,
                },
                files: {
                    'frontend/client/css/espo-vertical.css': 'frontend/less/espo-vertical/main.less',
                },
            },
            sakuraVertical: {
                options: {
                    yuicompress: true,
                },
                files: {
                    'frontend/client/css/sakura-vertical.css': 'frontend/less/sakura-vertical/main.less',
                },
            },
            greencoffeeVertical: {
                options: {
                    yuicompress: true,
                },
                files: {
                    'frontend/client/css/greencoffee-vertical.css': 'frontend/less/greencoffee-vertical/main.less',
                },
            }
        },
        cssmin: {
            minify: {
                files: {
                    'build/tmp/client/css/espo.css': [
                        'frontend/client/css/espo.css',
                    ]
                }
            },
        },
        uglify: {
            options: {
                mangle: false,
                banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
            },
            'build/tmp/client/espo.min.js': jsFilesToMinify.map(function (item) {
                return 'frontend/' + item;
            })
        },
        copy: {
            frontendFolders: {
                expand: true,
                cwd: 'frontend/client',
                src: [
                    'src/**',
                    'res/**',
                    'fonts/**',
                    'cfg/**',
                    'modules/**',
                    'img/**',
                    'css/**',
                    'sounds/**',
                    'custom/**'
                ],
                dest: 'build/tmp/client',
            },
            frontendHtml: {
                src: 'frontend/html/reset.html',
                dest: 'build/tmp/reset.html'
            },
            frontendLib: {
                expand: true,
                dot: true,
                cwd: 'frontend/client/lib',
                src: '**',
                dest: 'build/tmp/client/lib/',
            },
            backend: {
                expand: true,
                dot: true,
                src: [
                    'api/**',
                    'application/**',
                    'custom/**',
                    'data/.data',
                    'install/**',
                    'vendor/**',
                    'bootstrap.php',
                    'cron.php',
                    'rebuild.php',
                    'clear_cache.php',
                    'upgrade.php',
                    'extension.php',
                    'index.php',
                    'LICENSE.txt',
                    '.htaccess',
                    'web.config',
                ],
                dest: 'build/tmp/',
            },
            final: {
                expand: true,
                dot: true,
                src: '**',
                cwd: 'build/tmp',
                dest: 'build/EspoCRM-<%= pkg.version %>/',
            },
        },
        chmod: {
            options: {
                mode: '755'
            },
            php: {
                options: {
                    mode: '644'
                },
                src: [
                    'build/EspoCRM-<%= pkg.version %>/**/*.php',
                    'build/EspoCRM-<%= pkg.version %>/**/*.json',
                    'build/EspoCRM-<%= pkg.version %>/**/*.config',
                    'build/EspoCRM-<%= pkg.version %>/**/.htaccess',
                    'build/EspoCRM-<%= pkg.version %>/client/**/*.js',
                    'build/EspoCRM-<%= pkg.version %>/client/**/*.css',
                    'build/EspoCRM-<%= pkg.version %>/client/**/*.tpl',
                    'build/EspoCRM-<%= pkg.version %>/**/*.html',
                    'build/EspoCRM-<%= pkg.version %>/**/*.txt',
                ]
            }
        },
        replace: {
            timestamp: {
                options: {
                    patterns: [
                        {
                            match: 'timestamp',
                            replacement: '<%= new Date().getTime() %>'
                        }
                    ]
                },
                files: [
                    {
                        src: 'frontend/html/main.html',
                        dest: 'build/tmp/main.html'
                    }
                ]
            },
            version: {
                options: {
                    patterns: [
                        {
                            match: 'version',
                            replacement: '<%= pkg.version %>'
                        }
                    ]
                },
                files: [
                    {
                        src: 'build/tmp/application/Espo/Core/defaults/config.php',
                        dest: 'build/tmp/application/Espo/Core/defaults/config.php'
                    }
                ]
            }
        },
        compress: {
            final: {
                options: {
                    archive: 'build/EspoCRM-<%= pkg.version %>.zip',
                    mode: 'zip'
                },
                src: ['**'],
                cwd: 'build/EspoCRM-<%= pkg.version %>',
                dest: 'EspoCRM-<%= pkg.version %>'
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-mkdir');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-replace');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-chmod');

    grunt.registerTask('default', [
        'clean:start',
        'mkdir:tmp',
        'less',
        'cssmin',
        'uglify',
        'copy:frontendFolders',
        'copy:frontendHtml',
        'copy:frontendLib',
        'copy:backend',
        'replace',
        'copy:final',
        'chmod',
        'clean:final',
    ]);

};
