module.exports = function (grunt) {
	
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
			bootstrap: {
				options: {
					yuicompress: true,
				},
				files: {
					'frontend/client/css/bootstrap.css': 'frontend/less/espo/main.less',
				},
			},
		},
		cssmin: {
			minify: {
				files: {
					'build/tmp/client/css/espo.min.css': [
						'frontend/client/css/bootstrap.css',
						'frontend/client/css/datepicker.css',
						'frontend/client/css/jquery.timepicker.css',
					]
				}
			},
		},
		uglify: {
			options: {
				mangle: false,
				banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
			},
			'build/tmp/client/espo.min.js': [
					'frontend/client/lib/jquery-2.0.2.min.js',
					'frontend/client/lib/underscore-min.js',
					'frontend/client/lib/backbone-min.js',
					'frontend/client/lib/handlebars.js',
					'frontend/client/lib/base64.js',
					'frontend/client/lib/jquery-ui.min.js',
					'frontend/client/lib/moment.min.js',
					'frontend/client/lib/moment-timezone-with-data.min.js',
					'frontend/client/lib/jquery.timepicker.min.js',
					'frontend/client/lib/jquery.autocomplete.js',
					'frontend/client/lib/bootstrap.min.js',
					'frontend/client/lib/bootstrap-datepicker.js',
					'frontend/client/lib/bull.min.js',					
					'frontend/client/src/namespace.js',
					'frontend/client/src/exceptions.js',
					'frontend/client/src/app.js',
					'frontend/client/src/utils.js',
					'frontend/client/src/storage.js',
					'frontend/client/src/loader.js',
					'frontend/client/src/pre-loader.js',
					'frontend/client/src/ui.js',
					'frontend/client/src/acl.js',
					'frontend/client/src/model.js',
					'frontend/client/src/model-offline.js',
					'frontend/client/src/metadata.js',
					'frontend/client/src/language.js',
					'frontend/client/src/cache.js',
					'frontend/client/src/controller.js',
					'frontend/client/src/router.js',
					'frontend/client/src/date-time.js',
					'frontend/client/src/field-manager.js',
					'frontend/client/src/search-manager.js',
					'frontend/client/src/collection.js',
					'frontend/client/src/multi-collection.js',
					'frontend/client/src/view-helper.js',
					'frontend/client/src/layout-manager.js',
					'frontend/client/src/model-factory.js',
					'frontend/client/src/collection-factory.js',
					'frontend/client/src/models/settings.js',
					'frontend/client/src/models/user.js',
					'frontend/client/src/models/preferences.js',
					'frontend/client/src/controllers/base.js',
					'frontend/client/src/controllers/record.js',
					'frontend/client/src/controllers/role.js',
					'frontend/client/src/controllers/admin.js',
					'frontend/client/src/view.js',
					'frontend/client/src/views/base.js',
					'frontend/client/src/views/login.js',
			]
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
					'index.php',
					'LICENSE.txt',
					'.htaccess',
					'Web.config',
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
