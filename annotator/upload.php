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
 * This page saves the annotated PDF file to database
 *
 * @package    qtype_essayannotate
 * @copyright  2024 IIT Palakkad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @author Nideesh N, VM Sreeram,
 * Asha Jose, Parvathy S Kumar,
 * Tausif Iqbal, Vishal Rao (IIT Palakkad)
 * First version @link {https://github.com/TausifIqbal/moodle_quiz_annotator/blob/main/3.6/mod/quiz/upload.php}
 * Second version @link {https://github.com/Parvathy-S-Kumar/Moodle_Quiz_PDF_Annotator/blob/main/src/common/mod/quiz/upload.php}
 * This file is the third version, the changes from the previous version are as follows:
 * Followed security guidelines such as require_login, require_capability, required_param
 * instead of $_POST, escaping shell cmds before execution.
 * Added the logic to add steps to question_attempt_step after each annotation.
 * Modified the itemid of the file saved in the database to be the step_id of first annotation step we add.
 * Updated fileinfo array to fix unexpected annotations to different files with same filename within a quiz bug.
 *
 * This page saves annotated pdf to database.
 *
 * It gets the annotation data from pdfannotate.js as parameters. Then annotate the file using FPDI and FPDF
 * Then save it temporarily in 'essayPDF' subdirectory in Moodle's temp directory.
 *
 * Then create new file in databse using this temporary file.
 * Removed get_string usage in paths and changed to use helper functions
 */

require_once('../../../../config.php');
require_once('../../../../mod/quiz/locallib.php');
require(__DIR__ . '/annotatedfilebuilder.php');
require(__DIR__ . '/parser.php');
require(__DIR__ . '/alphapdf.php');
use qtype_essayannotate\helper;


/**
 * To convert PDF versions to 1.4 if the version is above it
 * since FPDI parser will only work for PDF versions upto 1.4.
 * Given a file and its path, the file converted to version 1.4 is returned,
 * if version is above it else, the original file is retured.
 *
 * @param string $file the path to the pdf file
 * @param string $essaypdfpath the path where the file exists
 * @param int $attemptid the attempt id
 * @param int $slot the slot id
 * @return $file the path to thepdf file after conversion (if necessary)
 */
function convert_pdf_version($file, $essaypdfpath, $attemptid, $slot) {
    global $USER;
    $filepdf = fopen($file, "r");
    if ($filepdf) {
        $linefirst = fgets($filepdf);
        preg_match_all('!\d+!', $linefirst, $matches);
        // Save that number in a variable.
        $pdfversion = implode('.', $matches[0]);
        if ($pdfversion > "1.4") {
            // Filename contains attemptid, slot, userid so that multiple files can be annotated simultaneously.
            $srcfilenew = $essaypdfpath . "/newdummy" . $attemptid . "$" . $slot . "$" . $USER->id . ".pdf";
            $srcfile = $file;
            // Using Ghostscript convert the pdf version to 1.4.
            // Getting Ghostscript path from settings page of plugin.
            $gspath = get_config('qtype_essayannotate', 'ghostscriptpath');
            $command = $gspath . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dBATCH -sOutputFile="' . $srcfilenew . '" "' . $srcfile . '"'.'  2>&1';
            $safecommand = escapeshellcmd($command);
            $shelloutput = shell_exec($safecommand);

            if (is_null($shelloutput)) {
                throw new moodle_exception('gs_fail', 'qtype_essayannotate');
            }
            $file = $srcfilenew;
            unlink($srcfile);          // To remove original dummy file.
        }
        fclose($filepdf);
    }

    return $file;
}

/**
 * Get the annotation step data based on the provided $markstep object.
 *
 * @param object $markstep The markstep object to retrieve data from.
 * @return array The array containing the submitted data.
 */
function get_annotation_stepdata($markstep) {
    $submitteddata = [];
    if ($markstep->get_state() != question_state::$unprocessed) {
        // So that the teacher's last manual comment is shown to students.
        $submitteddata["-maxmark"] = $markstep->get_qt_var("-maxmark");
        $submitteddata["-mark"] = $markstep->get_qt_var("-mark");
        $submitteddata["-commentformat"] = $markstep->get_qt_var("-commentformat");
        $submitteddata["-comment"] = $markstep->get_qt_var("-comment");
    } else {
        // So that the annotated step comment does not get revealed to students.
        $submitteddata["-comment"] = get_string('annotationstep_default_comment', 'qtype_essayannotate');
        $submitteddata["-mark"] = '';
    }
    return $submitteddata;
}

/**
 * Get the maximum number of bytes allowed for file uploads, considering various limits.
 * @return int The maximum number of bytes allowed for file uploads.
 */
function get_max_bytes() {
    global $CFG;
    $maxupload = (int)(ini_get('upload_max_filesize'));
    $maxpost = (int)(ini_get('post_max_size'));
    $memorylimit = (int)(ini_get('memory_limit'));
    $maxmb = min($maxupload, $maxpost, $memorylimit);
    $maxbytes = $maxmb * 1024 * 1024;

    $mdlmaxbytes = $CFG->maxbytes;
    if ($mdlmaxbytes > 0) {
        $maxbytes = min($maxbytes, $mdlmaxbytes);
    }
    return $maxbytes;
}

/**
 * Processes a question attempt step and saves it to the database.
 *
 * @param $quba The question usage by activity object.
 * @param $slot The slot where the question attempt is being made.
 * @param $submitteddata The data submitted for the question attempt.
 */
function add_question_attempt_step($quba, $slot, $submitteddata) {
    global $DB;
    $quba->process_action($slot, $submitteddata);

    // Saving the step to database.
    $transaction = $DB->start_delegated_transaction();
    question_engine::save_questions_usage_by_activity($quba);
    $transaction->allow_commit();
}

require_login();
// Getting all the data from pdfannotate.js.
$annotations = required_param('data', PARAM_RAW);                 // Changed id to data.
$contextid = required_param('contextid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);
$filename = required_param('filename', PARAM_FILE);
$usageid = required_param('usageid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$component = 'question';
$filearea = 'response_attachments';
$filepath = '/';

global $USER, $DB;

$result = helper::getcmid($attemptid);

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


// Get the serialisepdf value contents and convert into php arrays.
$json = json_decode($annotations, true);

// Referencing the file from the temp directory.
$essaypdfpath = $CFG->tempdir . '/' . helper::get_essaypdf_path();
$file = $essaypdfpath . '/' . helper::get_dummy_path() . $attemptid . "$" . $slot . "$" . $USER->id . ".pdf";
$tempfile = $essaypdfpath . '/outputmoodle' . $attemptid . "$" . $slot . "$" . $USER->id . ".pdf";

if (file_exists($file)) {
    // Calling function to convert the PDF version above 1.4 to 1.4 for compatibility with fpdf.
    $file = convert_pdf_version($file, $essaypdfpath, $attemptid, $slot);

    // Using FPDF and FPDI to annotate.
    if (file_exists($file)) {
        $pdf = build_annotated_file($file, $json);
        // Deleting dummy.pdf .
        unlink($file);
        // Creating output moodle file for loading into database.
        $pdf->Output('F', $tempfile);
    } else {
        throw new moodle_exception('pdf_version_error', 'qtype_essayannotate');
    }
} else {
    throw new moodle_exception('pdf_source_error', 'qtype_essayannotate');
}

if (file_exists($tempfile)) {
    $fsize = filesize($tempfile);       // File size of annotated file.
    $maxbytes = get_max_bytes();

    if (($fsize > 0) && ($maxbytes > 0) && ($fsize < $maxbytes)) {
        // Changes by Nideesh N and VM Sreeram for marking file for backup.
        $quba = question_engine::load_questions_usage_by_activity($usageid);
        $qa = $quba->get_question_attempt($slot);
        // Adding the annotation step to keep track of annotations in Response History.
        // In Response History, a new entry is added.
        $submitteddata = ["-comment" => get_string('annotated_file', 'qtype_essayannotate') . $filename];
        add_question_attempt_step($quba, $slot, $submitteddata);

        // Updating $qa after the step is saved.
        $quba = question_engine::load_questions_usage_by_activity($usageid);
        $qa = $quba->get_question_attempt($slot);

        // Saving the annotated file with $itemid as stepid of annotation step so that it gets marked for backup.
        $itemid = helper::get_first_annotation_comment_step($qa)->get_id();
        $fs = get_file_storage();
        $fileinfo = [
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'usage' => $usageid,
            'slot' => $slot,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'filename' => $filename, ];

        // Check if the annotated file exists in the file system and delete if so.
        $doesexists = $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);
        if ($doesexists === true) {
            $storedfile = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
            $storedfile->delete();
        }
        // Saving the new annotated file to the file system.
        $fs->create_file_from_pathname($fileinfo, $tempfile);

        // Getting the last feedback comment by teacher, and adding it once more
        // so that the teacher's last manual comment is shown to students.
        $markstep = $qa->get_last_step_with_qt_var("-mark");
        $submitteddata = get_annotation_stepdata($markstep);
        add_question_attempt_step($quba, $slot, $submitteddata);
    } else {
        throw new moodle_exception('file_too_big', 'qtype_essayannotate');
    }
    // Deleting outputmoodle file.
    unlink($tempfile);
} else {
    throw new moodle_exception('output_file_failed', 'qtype_essayannotate');
}
