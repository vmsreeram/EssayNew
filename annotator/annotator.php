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
 * @author Tausif Iqbal and Vishal Rao
 * @updatedby Asha Jose and Parvathy S Kumar (on lines 118 - 200)
 * @package   mod_quiz
 * @copyright gustav delius 2006
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once('../../../../mod/quiz/locallib.php');
require_login();        // added just now

global $USER;
//The $tempPath is the path to the subdirectory essayPDF created in moodle's temp directory 
$tempPath = $CFG->tempdir ."/essayPDF";

$attemptid = required_param('attempt', PARAM_INT);
$slot = required_param('slot', PARAM_INT); // The question number in the attempt.
$fileno = required_param('fileno', PARAM_INT);
$cmid = optional_param('cmid', null, PARAM_INT);
$dummyFile= $tempPath ."/dummy".$attemptid."$".$slot.$USER->id.".pdf";
if($cmid == null){
    global $DB;

    // SQL query
    $sql = "SELECT cm.id AS cmid
            FROM {quiz_attempts} qa
            JOIN {course_modules} cm ON qa.quiz = cm.instance AND cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
            WHERE qa.id = :attemptid";

    // Parameters for the query
    $params = ['attemptid' => $attemptid];

    // Execute the query
    $result = $DB->get_record_sql($sql, $params);
    // Check if a result is found
    if ($result) {
        // Access the cmid from the result
        $cmid = $result->cmid;
    } else {
        // If no result is found, throw a Moodle exception
        throw new moodle_exception('Some error occurred.');
    }
}

function get_first_annotation_comment_step($qa,$attemptid,$slotid) {
    foreach ($qa->get_step_iterator() as $step) {
        if ($step->has_qt_var("-comment") && is_int(strpos($step->get_qt_var("-comment"),"Annotated file: "))) {
            return $step;
        }
    }
    return new question_attempt_step_read_only();
}

// $PAGE->set_url('/mod/quiz/annotator.php', array('attempt' => $attemptid, 'slot' => $slot, 'fileno' => $fileno));
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

require_capability('mod/quiz:grade', $PAGE->context);  // added security feature
if(!is_dir($tempPath) && !mkdir($tempPath,0777,true)){
    throw new moodle_exception("Cannot create directory");
}
$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
$attemptobj->preload_all_attempt_step_users();

$que_for_commenting = $attemptobj->render_question_for_commenting($slot);

// we need $qa and $options to get all files submitted by student
$qa = $attemptobj->get_question_attempt($slot);
$qnum = $qa->get_slot();
$options = $attemptobj->get_display_options(true);

// get all the files
$files = $qa->get_last_qt_files('attachments', $options->context->id);

// select the "$fileno" file
$fileurl = "";
$currfileno = 0;
foreach ($files as $file) {
    $currfileno = $currfileno + 1;
    if($currfileno == $fileno)              // this is the file we want
    {
        $out = $qa->get_response_file_url($file);
        $url = (explode("?", $out))[0];     // remove ?forcedownload=1 from the end of the url
        $fileurl = $url;
        $originalFile = $file;             // storing it; in case the file is not PDF, we need the original file to create PDF from it
        break;
    }
}

// variable required to check if annotated file already exists 
// if exists, then render this file only (i.e. update the $fileurl)
$attemptid = $attemptobj->get_attemptid();
$contextid = $options->context->id;
$filename = explode("/", $fileurl);
$filename = end($filename);     //Changed
$filename = urldecode($filename);
// ////
// $filename = "Q" . $qnum . "_" . $filename;
// ////
$component = 'question';
$filearea = 'response_attachments';
$filepath = '/';
// $itemid = $attemptobj->get_attemptid();
$usageid = $qa->get_usage_id();
$itemid = get_first_annotation_comment_step($qa,$attemptid,$slot)->get_id();
if ($itemid == null)
    $itemid = $attemptid;

$canProceed=true;
// checking if file is not pdf
$format = explode(".", $filename);
$format = end($format);     //Changed
$ispdf = true;
$mime = explode(' ',get_mimetype_description($file))[0];
if($mime !== "PDF" && $format !== "pdf")
{
    $ispdf = false;
    $filename = (explode(".", $filename))[0] . "_topdf.pdf";
}

$fs = get_file_storage();

// check if the annotated pdf exists or not in database
$doesExists = $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);
if($doesExists === true)   // if exists then update $fileurl to the url of this file
{
    // the file object
    $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
    // create url of this file
    $file->copy_content_to($dummyFile);

    $url = file_encode_url(new moodle_url('/pluginfile.php'), '/' . implode('/', array(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $qa->get_usage_id(),
        $qa->get_slot(),
        $file->get_itemid())) .
        $file->get_filepath() . $file->get_filename(), true);
    
    $url = (explode("?", $url))[0];     // remove '"forcedownload=1' from the end of the url
    $fileurl = $url;                    // now update $fileurl
} else if($ispdf == false)
{
    // annotated PDF doesn't exists and the original file is not a PDF file
    // so we need to create PDF first and update fileurl to this PDF file

    //Changes made by Asha & Parvathy begins
    // copy non-pdf file to the temp directory of moodledata
    $fileToConvert=$tempPath . "/" . $originalFile->get_filename();
    // escapeshellcmd here
    $fileToConvert = escapeshellcmd($fileToConvert);
    $originalFile->copy_content_to($fileToConvert);
    
    // get the mime-type of the original file
    $mime = mime_content_type($fileToConvert);
    $mime = (explode("/", $mime))[0];

    // convert that file into PDF, based on mime type (NOTE: this will be created in the cwd)
    
    $convert = get_config('qtype_essayannotate', 'imagemagickpath');
    // $convert = '/usr/bin/convert';
    // $imagick = new Imagick();
    // $convert = "/opt/homebrew/bin/convert";
    if($mime === "image")
        $command = $convert." '" . $fileToConvert ."'  -page a4 " .$dummyFile;
        // $imagick->readImage($fileToConvert);
    else if($mime=="text")
    {
        $command = $convert." TEXT:'" . $fileToConvert ."' " .$dummyFile;
    }
    else
    {
        $canProceed=false;
        echo("Unsupported File");
        return;
        // throw new Exception("Unsupported File Type");
    }
   

    if($canProceed == true)
    {
        //Execute the commands of imagemagick(Convert texts and images to PDF)
        $safecommand = escapeshellcmd($command);
        // $safecommand = $command;
        $shellOutput = shell_exec($safecommand.'  2>&1');

        // $imagick->setSize(2480, 3508); // A4 size in points (72 points per inch)

        // // Save the resulting PDF file
        // $imagick->writeImages($dummyFile, true);

        // // Free up resources
        // $imagick->clear();
        // $imagick->destroy();

        // now delete that non-pdf file from tempPath; because we don't need it anymore
        
        $command = "rm '" . $fileToConvert . "'";
        $safecommand = escapeshellcmd($command);
        // $safecommand = $command;
        $shellOutput = shell_exec($safecommand.'  2>&1');

        // create a PDF file in moodle database from the above created PDF file
        $temppath = $dummyFile;
        $fileinfo = array(
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            // add usage and slot param
            'usage' => $usageid,
            'slot' => $slot,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'filename' => $filename);

        $fs->create_file_from_pathname($fileinfo, $temppath);   // create file

        // now update fileurl to this newly created PDF file
        $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
        // create url of this file
        $url = file_encode_url(new moodle_url('/pluginfile.php'), '/' . implode('/', array(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $qa->get_usage_id(),
            $qa->get_slot(),
            $file->get_itemid())) .
            $file->get_filepath() . $file->get_filename(), true);
        $url = (explode("?", $url))[0];     // remove '"forcedownload=1' from the end of the url
        $fileurl = $url;                    // now update $fileurl
    }
}
else   
{
    $originalFile->copy_content_to($dummyFile);
} 

//Checking if dummyfile was successfully created
if(!(file_exists($dummyFile)))
{
    $canProceed=false;
    throw new Exception("Permission  Denied");
}  

if($canProceed == true) 
{
    // include the html file; It has all the features of annotator
    // include "./index.html";

    $mustache = new Mustache_Engine;
    $data = array(
        'page_title' => 'PDF Annotation And Drawing Markup Plugin Example.',
        'bootstrap_css_url' => 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css',
        'font_awesome_css_url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
        'prettify_css_url' => 'https://cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css',
        'custom_css_url' => './styles.css',
        'pdfannotate_css_url' => './pdfannotate.css',
        'annotator_text' => 'Annotator',
        'font_sizes' => array(
            array('value' => '10'),
            array('value' => '12'),
            array('value' => '16', 'selected' => true),
            array('value' => '18'),
            array('value' => '24'),
            array('value' => '32'),
            array('value' => '48'),
            array('value' => '64'),
            array('value' => '72'),
            array('value' => '108')
        ),
        'color_tools' => array(
            array('color' => 'rgb(0, 0, 0)', 'active' => true),
            array('color' => 'rgb(251, 17, 17)'),
            array('color' => 'rgb(2, 2, 182)'),
            array('color' => 'rgb(13, 93, 13)'),
            array('color' => 'rgb(255, 255, 0)'),
        ),
        // 'select_button_icon' => 'fa fa-hand-pointer-o',
        // 'select_active' => true, // Example, set to true if select button should be active
        'other_tools' => array(
            array('id_select'=>true, 'icon_class' => 'fa fa-hand-pointer-o', 'onclick_action' => 'enableSelector(event)', 'tooltip' => 'Select', 'selected' => true),
            array('icon_class' => 'fa fa-pencil', 'onclick_action' => 'enablePencil(event)', 'tooltip' => 'Pen'),
            array('icon_class' => 'fa fa-font', 'onclick_action' => 'enableAddText(event)', 'tooltip' => 'Add Text'),
            array('icon_class' => 'fa fa-square-o', 'onclick_action' => 'enableRectangle(event)', 'tooltip' => 'Highlight Box'),
        ),
        'other_tools_extra' => array(
            array('select_danger'=>true,'icon_class' => 'fa fa-trash', 'onclick_action' => 'deleteSelectedObject(event)'),
            array('select_light'=>true,'icon_class' => 'fa fa-save', 'onclick_action' => 'savePDF(event)', 'select_savenexit' => true),
        ),
        'jquery_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js',
        'popper_js_url' => 'https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js',
        'popper_integrity' => 'sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN',
        'popper_crossorigin' => 'anonymous',
        'bootstrap_js_url' => 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js',
        'bootstrap_integrity' => 'sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV',
        'bootstrap_crossorigin' => 'anonymous',
        'pdf_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.min.js',
        'pdf_worker_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.worker.min.js',
        'fabric_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/4.3.0/fabric.min.js',
        'jspdf_js_url' => 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.2.0/jspdf.umd.min.js',
        'prettify_js_url' => 'https://cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js',
        'pdfannotate_js_url' => './pdfannotate.js',
        // 'clickhandlers_js_url' => './clickhandlers.js',
    );
    
    // Render the template with data
    $template = file_get_contents('../templates/indexhtml.mustache');
    echo $mustache->render($template, $data);    
}
//Changes made by Asha & Parvathy ends

?>
<!-- assigning php variable to javascript variable so that
     we can use these in javascript file
 -->
<script type="text/javascript">
    var fileurl = "<?= $fileurl ?>"
    var furl = "<?= $fileurl ?>"
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
