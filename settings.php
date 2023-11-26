<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Essay question renderer class.
 *
 * @package    qtype
 * @subpackage essay
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// $settings = new admin_settingpage('qtype_essaynew', get_string('pluginname', 'qtype_essaynew'));

if ($ADMIN->fulltree) {
$settings->add(new admin_setting_configtext(
    'qtype_essaynew/imagemagickpath',
    get_string('imagemagickpath', 'qtype_essaynew'),
    get_string('imagemagickpath_desc', 'qtype_essaynew'),
    '',
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'qtype_essaynew/ghostscriptpath',
    get_string('ghostscriptpath', 'qtype_essaynew'),
    get_string('ghostscriptpath_desc', 'qtype_essaynew'),
    '',
    PARAM_TEXT
));
}
// Add more settings as needed...

// $ADMIN->add('qtype_essaynew', $settings);