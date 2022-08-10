/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
 ************************************************************************/

/**
* * `grunt` - full build;
* * `grunt dev` - build only items needed for development (takes less time);
* * `grunt offline` - build but skip *composer install*;
* * `grunt internal` - build only libs and css;
* * `grunt release` - full build plus upgrade packages`;
* * `grunt test` - build for tests running;
* * `grunt run-tests` - build and run unit and integration tests.
*/

const fs = require('fs');
const cp = require('child_process');
const path = require('path');
const buildUtils = require('./js/build-utils');

module.exports = grunt => {

    const pkg = grunt.file.readJSON('package.json');
    const bundleConfig = require('./frontend/bundle-config.json');
    const libs = require('./frontend/libs.json');

    const originalLibDir = 'client/lib/original';

    let bundleJsFileList = buildUtils.getPreparedBundleLibList(libs).concat(originalLibDir + '/espo.js');
    let copyJsFileList = buildUtils.getCopyLibDataList(libs);

    let minifyLibFileList = copyJsFileList
        .filter(item => item.minify)
        .map(item => {
            return {
                dest: item.dest,
                src: item.originalDest,
            };
        });

    let currentPath = path.dirname(fs.realpathSync(__filename));

    let themeList = [];

    fs.readdirSync('application/Espo/Resources/metadata/themes').forEach(file => {
        themeList.push(file.substring(0, file.length - 5));
    });

    let cssminFilesData = {};
    let lessData = {};

    themeList.forEach(theme => {
        let name = buildUtils.camelCaseToHyphen(theme);

        let files = {};

        files['client/css/espo/'+name+'.css'] = 'frontend/less/'+name+'/main.less';
        files['client/css/espo/'+name+'-iframe.css'] = 'frontend/less/'+name+'/iframe/main.less';

        cssminFilesData['client/css/espo/'+name+'.css'] = 'client/css/espo/'+name+'.css';
        cssminFilesData['client/css/espo/'+name+'-iframe.css'] = 'client/css/espo/'+name+'-iframe.css';

        lessData[theme] = {
            options: {
                yuicompress: true,
            },
            files: files,
        };
    });

    grunt.initConfig({
        pkg: pkg,

        mkdir: {
            tmp: {
                options: {
                    mode: 0755,
                    create: [
                        'build/tmp',
                    ],
                }
            }
        },

        clean: {
            start: [
                'build/EspoCRM-*',
                'client/lib/*',
                'client/modules/crm/lib/*',
                'client/css/espo/*',
            ],
            final: ['build/tmp'],
            release: ['build/EspoCRM-' + pkg.version],
            beforeFinal: {
                src: [
                    'build/tmp/custom/Espo/Custom/*',
                    'build/tmp/custom/Espo/Modules/*',
                    '!build/tmp/custom/Espo/Custom/.htaccess',
                    '!build/tmp/custom/Espo/Modules/.htaccess',
                    'build/tmp/install/config.php',
                    'build/tmp/vendor/*/*/.git',
                    'build/tmp/custom/Espo/Custom/*',
                    'build/tmp/client/custom/*',
                    '!build/tmp/client/custom/modules',
                    'build/tmp/client/custom/modules/*',
                    '!build/tmp/client/custom/modules/dummy.txt',
                ]
            },
        },

        less: lessData,

        cssmin: {
            themes: {
                files: cssminFilesData,
            },
        },

        uglify: {
            options: {
                mangle: true,
                sourceMap: true,
                output: {
                    comments: /^!/,
                },
            },
            bundle: {
                options: {
                    banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
                },
                files: {
                    'client/lib/espo.min.js': bundleJsFileList,
                },
            },
            lib: {
                files: minifyLibFileList,
            },
        },

        copy: {
            options: {
                mode: true,
            },
            frontend: {
                expand: true,
                cwd: 'client',
                src: [
                    'src/**',
                    'res/**',
                    'fonts/**',
                    'cfg/**',
                    'modules/**',
                    'img/**',
                    'css/**',
                    'sounds/**',
                    'custom/**',
                    'lib/**',
                ],
                dest: 'build/tmp/client',
            },
            frontendLib: {
                files: copyJsFileList,
            },
            backend: {
                expand: true,
                dot: true,
                src: [
                    'application/**',
                    'custom/**',
                    'data/.data',
                    'vendor/**',
                    'html/**',
                    'public/**',
                    'install/**',
                    'bin/**',
                    'bootstrap.php',
                    'cron.php',
                    'daemon.php',
                    'rebuild.php',
                    'clear_cache.php',
                    'preload.php',
                    'upgrade.php',
                    'extension.php',
                    'websocket.php',
                    'command.php',
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

        replace: {
            version: {
                options: {
                    patterns: [
                        {
                            match: 'version',
                            replacement: '<%= pkg.version %>',
                        }
                    ]
                },
                files: [
                    {
                        src: 'build/tmp/application/Espo/Resources/defaults/config.php',
                        dest: 'build/tmp/application/Espo/Resources/defaults/config.php',
                    }
                ],
            },
            dev: {
                options: {
                    patterns: [
                        {
                            match: /\# \{\#dev\}(.*)\{\/dev\}/gs,
                            replacement: '',
                        }
                    ]
                },
                files: [
                    {
                        src: 'build/tmp/.htaccess',
                        dest: 'build/tmp/.htaccess',
                    }
                ],
            },
        },
    });

    grunt.registerTask('espo-bundle', () => {
        const Bundler = require('./js/bundler');

        let contents = (new Bundler()).bundle(bundleConfig.jsFiles);

        if (!fs.existsSync(originalLibDir)) {
            fs.mkdirSync(originalLibDir);
        }

        fs.writeFileSync(originalLibDir + '/espo.js', contents, 'utf8');
    });

    grunt.registerTask('prepare-lib-original', () => {
        // Even though `npm ci` runs the same script, 'clean:start' deletes files.
        cp.execSync("node js/scripts/prepare-lib-original");
    });

    grunt.registerTask('prepare-lib', () => {
        cp.execSync("node js/scripts/prepare-lib");
    });

    grunt.registerTask('chmod-folders', () => {
        cp.execSync(
            "find . -type d -exec chmod 755 {} +",
            {cwd: 'build/EspoCRM-' + pkg.version}
        );
    });

    grunt.registerTask('chmod-multiple', () => {
        let dirPath = 'build/EspoCRM-' + pkg.version;

        let fileList = [
            {
                name: '*.php',
            },
            {
                name: '*.json',
            },
            {
                name: '*.config',
            },
            {
                name: '.htaccess',
            },
            {
                name: '*.html',
            },
            {
                name: '*.txt',
            },
            {
                name: '*.js',
                folder: 'client',
            },
            {
                name: '*.css',
                folder: 'client',
            },
            {
                name: '*.tpl',
                folder: 'client',
            },
        ];

        let dirReadableList = [
            'public/install',
            'public/portal',
            'public/api',
            'public/api/v1',
            'public/api/v1/portal-access',
            '.',
        ];

        let dirWritableList = [
            'data',
            'custom',
            'custom/Espo',
            'custom/Espo/Custom',
            'client/custom',
            'client/modules',
            'application/Espo/Modules',
        ];

        fileList.forEach(item => {
            let path = item.folder || '.';
            let name = item.name;

            cp.execSync(
                `find ${path} -type f -iname "${name}" -exec chmod 644 {} +`,
                {
                    cwd: dirPath,
                }
            );
        });

        dirReadableList.forEach(item => {
            cp.execSync(
                `chmod 755 ${item}`,
                {
                    cwd: dirPath,
                }
            );
        });

        dirWritableList.forEach(item => {
            cp.execSync(
                `chmod 775 ${item}`,
                {
                    cwd: dirPath,
                }
            );
        });

        cp.execSync(
            `find bin -type f -exec chmod 754 {} +`,
            {
                cwd: dirPath,
            }
        );
    });

    grunt.registerTask('composer-install', () => {
        cp.execSync("composer install --no-dev", {stdio: 'ignore'});
    });

    grunt.registerTask('composer-install-dev', () => {
        cp.execSync("composer install", {stdio: 'ignore'});
    });

    grunt.registerTask('upgrade', () => {
        cp.execSync("node diff --all --vendor", {stdio: 'inherit'});
    });

    grunt.registerTask('unit-tests-run', () => {
        cp.execSync("vendor/bin/phpunit ./tests/unit", {stdio: 'inherit'});
    });

    grunt.registerTask('integration-tests-run', () => {
        cp.execSync("vendor/bin/phpunit ./tests/integration", {stdio: 'inherit'});
    });

    grunt.registerTask('set-config-params', () => {
        cp.execSync("composer run-script setConfigParams", {stdio: 'ignore'});
    });

    grunt.registerTask('zip', () => {
        const archiver = require('archiver');

        let resolve = this.async();

        let folder = 'EspoCRM-' + pkg.version;

        let zipPath = 'build/' + folder +'.zip';

        if (fs.existsSync(zipPath)) {
            fs.unlinkSync(zipPath);
        }

        let archive = archiver('zip');

        archive.on('error', err => {
            grunt.fail.warn(err);
        });

        let zipOutput = fs.createWriteStream(zipPath);

        zipOutput.on('close', () => {
            console.log("EspoCRM package has been built.");

            resolve();
        });

        archive
            .directory(currentPath + '/build/' + folder, folder)
            .pipe(zipOutput)
            .finalize();
    });

    grunt.registerTask('npm-install', () => {
        cp.execSync("npm ci", {stdio: 'ignore'});
    });

    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-mkdir');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-replace');

    grunt.registerTask('internal', [
        'less',
        'cssmin',
        'espo-bundle',
        'prepare-lib-original',
        'uglify:bundle',
        'copy:frontendLib',
        'prepare-lib',
        'uglify:lib',
    ]);

    grunt.registerTask('offline', [
        'clean:start',
        'mkdir:tmp',
        'internal',
        'copy:frontend',
        'copy:backend',
        'replace',
        'clean:beforeFinal',
        'copy:final',
        'chmod-folders',
        'chmod-multiple',
        'clean:final',
    ]);

    grunt.registerTask('build', [
        'composer-install',
        'npm-install',
        'set-config-params',
        'offline',
    ]);

    grunt.registerTask('default', [
        'build',
    ]);

    grunt.registerTask('release', [
        'build',
        'upgrade',
        'zip',
        'clean:release',
    ]);

    grunt.registerTask('run-tests', [
        'test',
        'unit-tests-run',
        'integration-tests-run',
    ]);

    grunt.registerTask('run-unit-tests', [
        'composer-install-dev',
        'unit-tests-run',
    ]);

    grunt.registerTask('run-integration-tests', [
        'test',
        'integration-tests-run',
    ]);

    grunt.registerTask('dev', [
        'composer-install-dev',
        'less',
    ]);

    grunt.registerTask('test', [
        'composer-install-dev',
        'npm-install',
        'offline',
    ]);
};
