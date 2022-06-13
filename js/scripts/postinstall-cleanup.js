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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

const fs = require('fs');

function rmDir(path) {
    if (fs.rm) {
        fs.rmSync(path, {recursive: true});

        return;
    }

    fs.rmdirSync(path, {recursive: true});
}

// Can't be ignored by IntelliJ IDE, resort to removing.
rmDir('./node_modules/vis/examples');
rmDir('./node_modules/vis/lib');
rmDir('./node_modules/vis/docs');
fs.unlinkSync('./node_modules/vis/gulpfile.js');
fs.unlinkSync('./node_modules/vis/index.js');
fs.unlinkSync('./node_modules/vis/index-graph3d.js');
fs.unlinkSync('./node_modules/vis/index-network.js');
fs.unlinkSync('./node_modules/vis/index-timeline-graph2d.js');
