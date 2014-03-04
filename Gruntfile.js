module.exports = function (grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		clean: {
			build: ['build/*'],
		},
		copy: {
			build: {
				expand: true,
				dot: true,
				src: [
					'api/**',
					'application/**',
					'data',
					'vendor/**',
					'bootstrap.php',
					'cron.php',
					'index.php',
					'LICENSE.txt',
					'.htaccess',
				],
				dest: 'build/',
			},
		},
	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');

	grunt.registerTask('default', [
		'clean',
		'copy',
	]);
};
