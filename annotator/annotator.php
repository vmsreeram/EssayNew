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
 * This page allows the teacher to annotate file of a particular question.
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
 *
 * @updatedby Nideesh, Sreeram
 * Followed security guidelines such as require_login, require_capability, escaping shell cmds before execution.
 * Used Mustache template to render HTML output. 
 * Updated logic for checking filetype is PDF by using mimetype and extension.
 * Updated itemid as the attemptid of the first annotation step.
 * Added logic to create `essayPDF` directory within temp directory of moodledata to store temporary files.
 */

require_once('annotatorMustacheConfig.php');
require_once('../../../../config.php');
require_once('../classes/helper.php');
require_once('../../../../mod/quiz/locallib.php');
require_login();

global $USER;

// The $tempPath is the path to the subdirectory essayPDF created in moodle's temp directory 
$tempPath = $CFG->tempdir ."/essayPDF";

$attemptid = required_param('attempt', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$fileno = required_param('fileno', PARAM_INT);
$cmid = optional_param('cmid', null, PARAM_INT);
$dummyFile= $tempPath ."/dummy".$attemptid."$".$slot.$USER->id.".pdf";
if($cmid == null){
    $result = helper::getCmid($attemptid) ;
    if ($result) {
        $cmid = $result->cmid;
    } else {
        throw new moodle_exception('Some error occurred.');
    }
}

$PAGE->set_url('/question/type/essayannotate/annotator/annotator.php', array('attempt' => $attemptid, 'slot' => $slot, 'fileno' => $fileno));

if (!empty($cmid)) {
    $cm = get_coursemodule_from_id('quiz', $cmid);
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);
}
else 
{
    throw new moodle_exception('Some error occurred.');
}

require_capability('mod/quiz:grade', $PAGE->context);

// Try to create the subdirectory if not exists
if(!is_dir($tempPath) && !mkdir($tempPath,0777,true)){
    throw new moodle_exception("Cannot create directory");
}
$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
$attemptobj->preload_all_attempt_step_users();
$que_for_commenting = $attemptobj->render_question_for_commenting($slot);

// We need $qa and $options to get all files submitted by student
$qa = $attemptobj->get_question_attempt($slot);
$options = $attemptobj->get_display_options(true);
$files = $qa->get_last_qt_files('attachments', $options->context->id);

// Select the "$fileno", the file about to be annotated
$fileurl = "";
$currfileno = 0;
foreach ($files as $file) {
    $currfileno = $currfileno + 1;
    if($currfileno == $fileno)
    {
        $out = $qa->get_response_file_url($file);
        $url = (explode("?", $out))[0];     // remove '?forcedownload=1' from the end of the url
        $fileurl = $url;
        $originalFile = $file;              // storing it; in case the file is not PDF, we need the original file to create PDF from it
        break;
    }
}

$attemptid = $attemptobj->get_attemptid();
$contextid = $options->context->id;
$filename = explode("/", $fileurl);
$filename = end($filename);
$filename = urldecode($filename);
$component = 'question';
$filearea = 'response_attachments';
$filepath = '/';
$usageid = $qa->get_usage_id();
$itemid = helper::get_first_annotation_comment_step($qa)->get_id();

// If uploaded file is not PDF, the converted file will have to be saved in file area. An $itemid is required for it.
if ($itemid == null)
    $itemid = $attemptid;

$canProceed=true;
$format = explode(".", $filename);
$format = end($format);
$ispdf = true;
$mime = explode(' ',get_mimetype_description($file))[0];

// Checking if file is not PDF
if($mime !== "PDF" && $format !== "pdf")
{
    $ispdf = false;
    $filename = (explode(".", $filename))[0] . "_topdf.pdf";
}

$fs = get_file_storage();

// Check if the annotated PDF exists in database
$doesExists = $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);

// If exists, then update $fileurl to the url of this file
if($doesExists === true)
{
    $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
    $file->copy_content_to($dummyFile);
    
    // Create url of this file
    $url = file_encode_url(new moodle_url('/pluginfile.php'), '/' . implode('/', array(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $qa->get_usage_id(),
        $qa->get_slot(),
        $file->get_itemid())) .
        $file->get_filepath() . $file->get_filename(), true);
    
    $url = (explode("?", $url))[0];     // remove '?forcedownload=1' from the end of the url
    $fileurl = $url;                    // now update $fileurl
} else if($ispdf == false)
{
    // Annotated PDF doesn't exists and the original file is not a PDF file
    // So we need to create PDF first and update fileurl to this PDF file

    // Copy non-PDF file to the temp directory of moodledata
    $fileToConvert=$tempPath . "/" . $originalFile->get_filename();
    $fileToConvert = escapeshellcmd($fileToConvert);
    $originalFile->copy_content_to($fileToConvert);
    
    // Get the mime-type of the original file
    $mime = mime_content_type($fileToConvert);
    $mime = (explode("/", $mime))[0];

    // Convert that file into PDF, based on mime type
    
    $convert = get_config('qtype_essayannotate', 'imagemagickpath');
    
    if($mime === "image")
        $command = $convert." '" . $fileToConvert ."'  -page a4 " .$dummyFile;
    else if($mime=="text")
    {
        $command = $convert." TEXT:'" . $fileToConvert ."' " .$dummyFile;
    }
    else
    {
        $canProceed=false;
        echo("Unsupported File");
        return;
    }

    if($canProceed == true)
    {
        // Execute the commands of imagemagick(Convert texts and images to PDF)
        $safecommand = escapeshellcmd($command);
        $shellOutput = shell_exec($safecommand.'  2>&1');
   
        $command = "rm '" . $fileToConvert . "'";
        $safecommand = escapeshellcmd($command);
        $shellOutput = shell_exec($safecommand.'  2>&1');

        // Create a PDF file in moodle database from the above created PDF file
        $temppath = $dummyFile;
        $fileinfo = array(
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'usage' => $usageid,
            'slot' => $slot,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'filename' => $filename);

        $fs->create_file_from_pathname($fileinfo, $temppath);

        // Now update fileurl to this newly created PDF file
        $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
        // Create url of this file
        $url = file_encode_url(new moodle_url('/pluginfile.php'), '/' . implode('/', array(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $qa->get_usage_id(),
            $qa->get_slot(),
            $file->get_itemid())) .
            $file->get_filepath() . $file->get_filename(), true);
        $url = (explode("?", $url))[0];     // remove '?forcedownload=1' from the end of the url
        $fileurl = $url;                    // now update $fileurl
    }
}
else   
{
    $originalFile->copy_content_to($dummyFile);
} 

// Checking if creation of dummyfile was unsuccessful
if(!(file_exists($dummyFile)))
{
    $canProceed=false;
    throw new Exception("Permission  Denied");
}  

if($canProceed == true) 
{
    // Render the annotator UI
    $mustache = new Mustache_Engine;
    $data = new annotatorMustacheConfig();
    $template = file_get_contents('../templates/annotator.mustache');
    echo $mustache->render($template, $data);    
}

?>
<!-- assigning php variable to javascript variable so that
     we can use these in javascript file
 -->
<script type="text/javascript">
    var fileurl = "<?= $fileurl ?>"
    var contextid = "<?= $contextid ?>";
    var attemptid = "<?= $attemptid ?>";
    var filename = "<?= $filename ?>"; 
    var filearea = "<?= $filearea ?>"; 
    var filepath = "<?= $filepath ?>"; 
    var component = "<?= $component ?>"; 
    var usageid = "<?= $usageid ?>"; 
    var slot = "<?= $slot ?>"; 
</script>
<script type="text/javascript" src="./clickhandlers.js"></script>
