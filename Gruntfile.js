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

/**
* * `grunt` - full build;
* * `grunt dev` - build for development;
* * `grunt offline` - build but skip *composer install*;
* * `grunt internal` - build only libs and css;
* * `grunt release` - full build zipped with upgrade packages`;
* * `grunt test` - build for tests running;
* * `grunt run-tests` - build and run unit and integration tests.
*/

const fs = require('fs');
const cp = require('child_process');
const path = require('path');
const buildUtils = require('./js/build-utils');
const {TemplateBundler, Bundler} = require('espo-frontend-build-tools');
const LayoutTypeBundler = require('./js/layout-template-bundler');

const bundleConfig = require('./frontend/bundle-config.json');
const libs = require('./frontend/libs.json');

module.exports = grunt => {

    const pkg = grunt.file.readJSON('package.json');

    const originalLibDir = 'client/lib/original';

    const libsBundleFileList = [
        'client/src/namespace.js',
        'client/src/loader.js',
        ...buildUtils.getPreparedBundleLibList(libs),
    ];

    const bundleFileMap = {'client/lib/espo.js': libsBundleFileList};

    for (const name in bundleConfig.chunks) {
        const namePart = 'espo-' + name;

        bundleFileMap[`client/lib/${namePart}.js`] = originalLibDir + `/${namePart}.js`
    }

    const copyJsFileList = buildUtils.getCopyLibDataList(libs);

    const minifyLibFileList = copyJsFileList
        .filter(item => item.minify)
        .map(item => {
            return {
                dest: item.dest,
                src: item.originalDest,
            };
        });

    const currentPath = path.dirname(fs.realpathSync(__filename));

    const themeList = [];

    fs.readdirSync('application/Espo/Resources/metadata/themes').forEach(file => {
        themeList.push(file.substring(0, file.length - 5));
    });

    const cssminFilesData = {};
    const lessData = {};

    themeList.forEach(theme => {
        const name = buildUtils.camelCaseToHyphen(theme);

        const files = {};

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

    const cleanupBeforeFinal = [
        'build/tmp/custom/Espo/Custom/*',
        '!build/tmp/custom/Espo/Custom/.htaccess',
        '!build/tmp/custom/Espo/Modules',
        'build/tmp/custom/Espo/Modules/*',
        '!build/tmp/custom/Espo/Modules/.htaccess',
        'build/tmp/install/config.php',
        'build/tmp/vendor/*/*/.git',
        'build/tmp/client/custom/*',
        '!build/tmp/client/custom/modules',
        'build/tmp/client/custom/modules/*',
        '!build/tmp/client/custom/modules/dummy.txt',
        'build/tmp/client/modules/crm/src',
        'build/tmp/client/lib/original/*',
        'build/tmp/client/modules/crm/lib/original',
        '!build/tmp/client/lib/original/espo.js',
        '!build/tmp/client/lib/original/espo-*.js',
        '!build/tmp/client/lib/original/espo-funnel-chart.js',
        'build/tmp/client/lib/transpiled',
    ];

    const cleanupBeforeFinalTest = cleanupBeforeFinal.filter(it => {
        return ![
            '!build/tmp/custom/Espo/Modules',
            'build/tmp/custom/Espo/Modules/*',
            '!build/tmp/custom/Espo/Modules/.htaccess',
            '!build/tmp/client/custom/modules',
            'build/tmp/client/custom/modules/*',
            '!build/tmp/client/custom/modules/dummy.txt',
        ].includes(it)
    })

    grunt.initConfig({
        pkg: pkg,

        mkdir: {
            tmp: {
                options: {
                    mode: 0o755,
                    create: [
                        'build/tmp',
                    ],
                }
            }
        },

        clean: {
            transpiled: [
                'client/lib/transpiled/**',
            ],
            start: [
                'build/EspoCRM-*',
                'client/lib/*',
                'client/modules/crm/lib/*',
                'client/css/espo/*',
            ],
            final: ['build/tmp'],
            release: ['build/EspoCRM-' + pkg.version],
            beforeFinal: {src: cleanupBeforeFinal},
            beforeFinalTest: {src: cleanupBeforeFinalTest},
        },

        less: lessData,

        cssmin: {
            themes: {
                files: cssminFilesData,
            },
        },

        uglify: {
            options: {
                sourceMap: true,
                output: {
                    comments: /^!/,
                },
                beautify: false,
                compress: {
                    sequences: false,
                    collapse_vars: false,
                    keep_fargs: true,
                    webkit: true,
                },
            },
            bundle: {
                options: {
                    banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
                },
                files: bundleFileMap,
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
                    'res/**',
                    'fonts/**',
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
                            match: /# \{#dev}(.*)\{\/dev}/gs,
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

    const writeOriginalLib = (name, contents) => {
        if (!fs.existsSync(originalLibDir)) {
            fs.mkdirSync(originalLibDir);
        }

        const file = originalLibDir + `/${name}.js`;

        fs.writeFileSync(file, contents, 'utf8');
    };

    grunt.registerTask('bundle', () => {
        const bundler = new Bundler(bundleConfig, libs);

        const result = bundler.bundle();

        for (const name in result) {
            let contents = result[name];

            const key = 'espo-' + name;

            if (name === 'main') {
                contents += '\n' + (new LayoutTypeBundler()).bundle();
            }

            writeOriginalLib(key, contents);
        }
    });

    grunt.registerTask('bundle-templates', () => {
        const templateBundler = new TemplateBundler({
            dirs: [
                'client/res/templates',
                'client/modules/crm/res/templates',
            ],
        });

        templateBundler.process();
    });

    grunt.registerTask('prepare-lib-original', () => {
        // Even though `npm ci` runs the same script, 'clean:start' deletes files.
        cp.execSync("node js/scripts/prepare-lib-original");
    });

    grunt.registerTask('prepare-lib', () => {
        cp.execSync("node js/scripts/prepare-lib");
    });

    grunt.registerTask('transpile', () => {
        cp.execSync("node js/transpile");
    });

    grunt.registerTask('chmod-folders', () => {
        cp.execSync(
            "find . -type d -exec chmod 755 {} +",
            {cwd: 'build/EspoCRM-' + pkg.version}
        );
    });

    grunt.registerTask('chmod-multiple', () => {
        const dirPath = 'build/EspoCRM-' + pkg.version;

        const fileList = [
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

        const dirReadableList = [
            'public/install',
            'public/portal',
            'public/api',
            'public/api/v1',
            'public/api/v1/portal-access',
            '.',
        ];

        const dirWritableList = [
            'data',
            'custom',
            'custom/Espo',
            'custom/Espo/Custom',
            'client/custom',
            'client/modules',
            'application/Espo/Modules',
        ];

        fileList.forEach(item => {
            const path = item.folder || '.';
            const name = item.name;

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
            `find bin -type f -exec chmod 755 {} +`,
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
        cp.execSync("node diff --closest", {stdio: 'inherit'});
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

    grunt.registerTask('zip', function () { // Don't change to arrow-function.
        const archiver = require('archiver');

        const resolve = this.async();

        const folder = 'EspoCRM-' + pkg.version;
        const zipPath = 'build/' + folder + '.zip';

        if (fs.existsSync(zipPath)) {
            fs.unlinkSync(zipPath);
        }

        const archive = archiver('zip');

        archive.on('error', err => {
            grunt.fail.warn(err);
        });

        const zipOutput = fs.createWriteStream(zipPath);

        zipOutput.on('close', () => {
            console.log("EspoCRM package has been built.");

            resolve();
        });

        archive
            .directory(currentPath + '/build/' + folder, folder)
            .pipe(zipOutput);

        archive.finalize();
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
        'prepare-lib-original',
        'clean:transpiled',
        'transpile',
        'bundle',
        'bundle-templates',
        'uglify:bundle',
        'copy:frontendLib',
        'prepare-lib',
        'uglify:lib',
    ]);

    const offline = [
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
    ];

    const offlineTest = offline.map(it => {
        if (it === 'clean:beforeFinal') {
            return 'clean:beforeFinalTest'
        }

        return it;
    })

    grunt.registerTask('offline', offline);
    grunt.registerTask('offline-test', offlineTest);

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
        'npm-install',
        'internal',
    ]);

    grunt.registerTask('test', [
        'composer-install-dev',
        'npm-install',
        'offline-test',
    ]);
};
