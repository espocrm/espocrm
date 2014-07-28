var versionFrom = process.argv[2];

if (process.argv.length < 3) {
	throw new Error("No 'version from' passed");
}

var path = require('path');
var fs = require('fs');
var sys = require('sys')

var version = (require('./package.json') || {}).version;

var currentPath = path.dirname(fs.realpathSync(__filename));

var buildRelPath = 'build/EspoCRM-' + version;
var buildPath = currentPath + '/' + buildRelPath;
var diffFilePath = currentPath + '/build/diff';

var upgradePath = currentPath + '/build/EspoCRM-upgrade-' + versionFrom + '-' + version;


var exec = require('child_process').exec;

function execute(command, callback) {
	exec(command, function(error, stdout, stderr) {
    	callback(stdout);
    });
};

execute('git diff --name-only ' + versionFrom, function (stdout) {
	if (!fs.existsSync(upgradePath)) {
		fs.mkdirSync(upgradePath);
	}
	if (!fs.existsSync(upgradePath + '/files')) {
		fs.mkdirSync(upgradePath + '/files');
	}
	
	var fileList = [];
	
	(stdout || '').split('\n').forEach(function (file) {
		if (file == '') {
			return;
		}
		fileList.push(file.replace('frontend/', ''));
	});
	
	fileList.push('client/espo.min.js');
	
	fs.writeFileSync(diffFilePath, fileList.join('\n'));
	
	execute('git diff --name-only --diff-filter=D ' + versionFrom, function (stdout) {
		var deletedFileList = [];
		
		(stdout || '').split('\n').forEach(function (file) {
			if (file == '') {
				return;
			}
			deletedFileList.push(file.replace('frontend/', ''));
		});	
		
		process.chdir(buildPath);
		execute('xargs -a ' + diffFilePath + ' cp --parents -t ' + upgradePath + '/files ' , function (stdout) {
	
		});
		
		var d = new Date();
		var date = d.getFullYear().toString() + '-' + (d.getMonth() + 1).toString() + '-' + (d.getDate()).toString();
		
		var manifest = {
			"name": "EspoCRM Upgrade "+versionFrom+" to "+version,
			"version": version,
			"acceptableVersions": [
				versionFrom
			],
			"releaseDate": date,
			"author": "EspoCRM",			
			"description": "",
			"delete": deletedFileList
		}
		
		fs.writeFileSync(upgradePath + '/manifest.json', JSON.stringify(manifest, null, '  '));
		
		fs.unlinkSync(diffFilePath)
	
	});
	
});

