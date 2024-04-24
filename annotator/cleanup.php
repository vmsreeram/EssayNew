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
 * This page cleans up dummy file when the user closes the annotator ui.
 *
 * @author Nideesh N, VM Sreeram,
 * @package    qtype_essayannotate
 * @copyright  2024 IIT Palakkad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once('../../../../mod/quiz/locallib.php');
require_once('../classes/helper.php');
require(__DIR__ . '/annotatedfilebuilder.php');
require(__DIR__ . '/parser.php');
require(__DIR__ . '/alphapdf.php');


require_login();
// Getting all the data from pdfannotate.js
$attemptid = required_param('attemptid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);

global $USER, $DB;

$result = helper::getCmid($attemptid);

if ($result) {
    $cmid = $result->cmid;
} else {
    throw new moodle_exception('generic_error', 'qtype_essayannotate');
}

if (!empty($cmid)) {
    $cm = get_coursemodule_from_id('quiz', $cmid);
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);
}

require_capability('mod/quiz:grade', $PAGE->context);

// Referencing the file from the temp directory
$essaypdfpath = $CFG->tempdir . get_string('essayPDF_path', 'qtype_essayannotate');
$file = $essaypdfpath . get_string('dummy_path', 'qtype_essayannotate') . $attemptid . "$" . $slot . "$" . $USER->id . ".pdf";
$tempfile = $essaypdfpath . '/outputmoodle' . $attemptid . "$" . $slot . "$" . $USER->id . ".pdf";

if (file_exists($file)) {
    // Calling function to delete the dummy file
    unlink($file);
}
