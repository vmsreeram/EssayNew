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
 * @package    qtype
 * @subpackage essayannotate
 * @copyright  2024 IIT Palakkad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @author Tausif Iqbal, Vishal Rao
 * @link {https://github.com/TausifIqbal/moodle_quiz_annotator}
 * 
 * @updatedby Asha Jose, Parvathy S Kumar
 * @link {https://github.com/Parvathy-S-Kumar/Moodle_Quiz_PDF_Annotator}
 * All parts of this file excluding preparing the file record object and adding file to the database is modified
 *
 * @updatedby Nideesh, Sreeram
 * Followed security guidelines such as require_login, require_capability, required_param instead of $_POST, escaping shell cmds before execution.
 * Added the logic to add steps to question_attempt_step after each annotation. 
 * Modified the itemid of the file saved in the database to be the step_id of first annotation step we add.
 * Updated fileinfo array to fix unexpected annotations to different files with same filename within a quiz bug.
 *
 * This page saves annotated pdf to database.
 * 
 * It gets the annotation data from JavaScript through POST request. Then annotate the file using FPDI and FPDF
 * Then save it temporarily in this directory.
 *
 * Then create new file in databse using this temporary file.
 */

require_once('../../../../config.php');
require_once('../../../../mod/quiz/locallib.php');
require_once('../classes/helper.php');
require __DIR__ . '/annotatedfilebuilder.php';
require __DIR__ . '/parser.php';
require __DIR__ . '/alphapdf.php';


/**
 * To convert PDF versions to 1.4 if the version is above it
 * since FPDI parser will only work for PDF versions upto 1.4.
 * Given a file and its path, the file converted to version 1.4 is returned,
 * if version is above it else, the original file is retured.
 *
 * @param string $file the pdf file
 * @param string $path the path where the file exists
 * @param int $attemptid the attempt id
 * @param int $slot the slot id
 * @return $file the pdf file after conversion is done if necessary
 */
function convert_pdf_version($file, $path, $attemptid, $slot)
{
    global $USER;
    $filepdf = fopen($file,"r");
    if ($filepdf) 
    {
        $line_first = fgets($filepdf);
        preg_match_all('!\d+!', $line_first, $matches);	
        // save that number in a variable
        $pdfversion = implode('.', $matches[0]);
        if($pdfversion > "1.4")
        {
            // Filename contains attemptid, slot, userid so that multiple files can be annotated simultaneously
            $srcfile_new = $path."/newdummy".$attemptid."$".$slot.$USER->id.".pdf";;
            $srcfile = $file;
            // Using GhostScript convert the pdf version to 1.4
            // Getting GhostScript path from settings page of plugin
            $gsPath = get_config('qtype_essayannotate', 'ghostscriptpath');

            $command = $gsPath . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dBATCH -sOutputFile="' . $srcfile_new . '" "' . $srcfile . '"'.'  2>&1';
            $safecommand = escapeshellcmd($command);
            $shellOutput = shell_exec($safecommand);

            if(is_null($shellOutput))
            {
                throw new Exception("PDF conversion using GhostScript failed");
            }
            $file = $srcfile_new;
            unlink($srcfile);          // to remove original dummy.pdf
        }   
        fclose($filepdf);
    }

    return $file;
}

require_login();

// Getting all the data from pdfannotate.js
$value = required_param('id', PARAM_RAW);
$contextid = required_param('contextid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);
$filename = required_param('filename', PARAM_FILE);
$usageid = required_param('usageid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$component = 'question';
$filearea = 'response_attachments';
$filepath = '/';

global $USER,$DB;

// Getting the cmid
$result = helper::getCmid($attemptid);

if ($result) {
    $cmid = $result->cmid;
} else {
    throw new moodle_exception('Some error occurred.');
}

if (!empty($cmid)) {
    $cm = get_coursemodule_from_id('quiz', $cmid); 
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);
}

require_capability('mod/quiz:grade', $PAGE->context); 


// Get the serialisepdf value contents and convert into php arrays
$json = json_decode($value,true);

// Referencing the file from the temp directory 
$path = $CFG->tempdir . '/essayPDF';
$file = $path . '/dummy'.$attemptid."$".$slot.$USER->id.".pdf"; 
$tempfile = $path . '/outputmoodle'.$attemptid."$".$slot.$USER->id.".pdf";

if(file_exists($file))
{
    // Calling function to convert the PDF version above 1.4 to 1.4 for compatibility with fpdf
    $file=convert_pdf_version($file, $path, $attemptid, $slot);

    // Using FPDF and FPDI to annotate
    if(file_exists($file))
    {
        $pdf = build_annotated_file($file, $json);
        // Deleting dummy.pdf
        unlink($file);
        // Creating output moodle file for loading into database
        $pdf->Output('F', $tempfile);
    }
    else
        throw new Exception('\nPDF Version incompatible'); 
}
else
    throw new Exception('\nSource PDF not found!'); 

if(file_exists($tempfile))
{
    $fsize = filesize($tempfile);
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $max_mb = min($max_upload, $max_post, $memory_limit);
    $maxbytes = $max_mb*1024*1024;

    $mdl_maxbytes = $CFG->maxbytes;
    if($mdl_maxbytes > 0)
    {
        $maxbytes = min($maxbytes, $mdl_maxbytes);
    }
    if(($fsize > 0) && ($maxbytes > 0) && ($fsize < $maxbytes))
    {
        $quba = question_engine::load_questions_usage_by_activity($usageid);       
        $qa = $quba->get_question_attempt($slot);
        // Adding the annotation step to keep track of annotations in Response History
        $submitteddata = array("-comment"=>get_string('annotated_file','qtype_essayannotate'). $filename . get_string('user','qtype_essayannotate') . $USER->firstname ." " . $USER->lastname. get_string('time','qtype_essayannotate') . date("'Y-m-d H:i:s'",time()) . ".");
        $quba->process_action($slot, $submitteddata, null);

        // Saving the step to database
        $transaction = $DB->start_delegated_transaction();
        question_engine::save_questions_usage_by_activity($quba);
        $transaction->allow_commit();

        $quba = question_engine::load_questions_usage_by_activity($usageid);       
        $qa = $quba->get_question_attempt($slot);

        // saving the annotated file with $itemid as stepid of annotation step so that it gets marked for backup
        $itemid = helper::get_first_annotation_comment_step($qa)->get_id();
        $fs = get_file_storage();
        $fileinfo = array(
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'usage' => $usageid,
            'slot' => $slot,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'filename' => $filename);
        
            $doesExists = $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);
            if($doesExists === true)
            {
                $storedfile = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
                $storedfile->delete();
            }
        
        $fs->create_file_from_pathname($fileinfo, $tempfile); 

        $submitteddata = array();
        $markstep = $qa->get_last_step_with_qt_var("-mark");
        if ($markstep->get_state() != question_state::$unprocessed) {
            // So that the teacher's last manual comment is shown to students
            $submitteddata["-maxmark"] = $markstep->get_qt_var("-maxmark");
            $submitteddata["-mark"] = $markstep->get_qt_var("-mark");
            $submitteddata["-commentformat"] = $markstep->get_qt_var("-commentformat");
            $submitteddata["-comment"] = $markstep->get_qt_var("-comment");
        }
        else
        {
            // So that the annotated step comment does not get revealed to students
            $submitteddata["-comment"]=get_string('annotationstep_default_comment','qtype_essayannotate');
            $submitteddata["-mark"]='';
        }
        $quba = question_engine::load_questions_usage_by_activity($usageid);
        $quba->process_action($slot, $submitteddata, null);

        $transaction = $DB->start_delegated_transaction();
        question_engine::save_questions_usage_by_activity($quba);
        $transaction->allow_commit();
        
    }
    else
    {
        throw new Exception("Too big file");
    }
    // Deleting outputmoodle.pdf
    unlink($tempfile);
}
else
{
    throw new Exception("Failed to create output file");
}
?>

