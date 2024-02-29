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
 * essayannotate question renderer class.
 *
 * @package    qtype
 * @subpackage essayannotate
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// $settings = new admin_settingpage('qtype_essayannotate', get_string('pluginname', 'qtype_essayannotate'));

if ($ADMIN->fulltree) {
$settings->add(new admin_setting_configtext(
    'qtype_essayannotate/imagemagickpath',
    get_string('imagemagickpath', 'qtype_essayannotate'),
    get_string('imagemagickpath_desc', 'qtype_essayannotate'),
    'convert',
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'qtype_essayannotate/ghostscriptpath',
    get_string('ghostscriptpath', 'qtype_essayannotate'),
    get_string('ghostscriptpath_desc', 'qtype_essayannotate'),
    'gs',
    PARAM_TEXT
));

$settings->add(new admin_setting_heading(
    'qtype_essayannotate/settingsnote',
    get_string('settingsnote', 'qtype_essayannotate'),
    ''
));
}
// Add more settings as needed...

// $ADMIN->add('qtype_essayannotate', $settings);