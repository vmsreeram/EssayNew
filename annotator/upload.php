<?php
/**
 * @author Tausif Iqbal, Vishal Rao
 * @updatedby Asha Jose, Parvathy S Kumar
 * All parts of this file excluding preparing the file record object and adding file to the database is modified by us
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
require __DIR__ . '/annotatedfilebuilder.php';
require __DIR__ . '/parser.php';
require __DIR__ . '/alphapdf.php';
// putenv('PATH=/bin:/usr/bin:/opt/homebrew/bin/gs');

function get_last_step_with_qt_var_and_value($qa,$name) {
    foreach ($qa->get_reverse_step_iterator() as $step) {
        if ($step->has_qt_var($name) && is_int(strpos($step->get_qt_var($name),"Annotated file. "))) {
            return $step;
        }
    }
    return null;
}

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
    $filepdf = fopen($file,"r");
    if ($filepdf) 
    {
        $line_first = fgets($filepdf);
        preg_match_all('!\d+!', $line_first, $matches);	
        // save that number in a variable
        $pdfversion = implode('.', $matches[0]);
        if($pdfversion > "1.4")
        {
            $srcfile_new = $path."/newdummy".$attemptid."$".$slot.".pdf";;
            $srcfile = $file;
            //Using GhostScript convert the pdf version to 1.4
            $gsPath = get_config('qtype_essayannotate', 'ghostscriptpath');

            // $gsPath = '/usr/bin/gs';
            // $gsPath = '/opt/homebrew/bin/gs';
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

//Getting all the data from mypdfannotate.js
require_login();
// add cap req : grade
$value = required_param('id', PARAM_RAW);
$contextid = required_param('contextid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);
$filename = required_param('filename', PARAM_FILE);
$usageid = required_param('usageid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$component = 'question';
$filearea = 'response_attachments';
$filepath = '/';

// creating context
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
    throw new moodle_exception('No result found for the given attemptid'. $attemptid);
}

if (!empty($cmid)) {
    $cm = get_coursemodule_from_id('quiz', $cmid); 
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);
}

require_capability('mod/quiz:grade', $PAGE->context); 


//Get the serialisepdf value contents and convert into php arrays
$json = json_decode($value,true);

//Referencing the file from the temp directory 
$path = $CFG->tempdir . '/essayPDF';
$file = $path . '/dummy'.$attemptid."$".$slot.".pdf"; 
$tempfile = $path . '/outputmoodle'.$attemptid."$".$slot.".pdf";

if(file_exists($file))
{
    //Calling function to convert the PDF version above 1.4 to 1.4 for compatibility with fpdf
    $file=convert_pdf_version($file, $path, $attemptid, $slot);

    //Using FPDF and FPDI to annotate
    if(file_exists($file))
    {
        $pdf = build_annotated_file($file, $json);
        // Deleting dummy.pdf
        unlink($file);
        // creating output moodle file for loading into database
        $pdf->Output('F', $tempfile);
    }
    else
        throw new Exception('\nPDF Version incompatible'); 
}
else
    throw new Exception('\nSource PDF not found!'); 

if(file_exists($tempfile))
{
    //Lines 111 - 149 are pulled from Tausif Iqbal and Vishal Rao version 3.6
    $fsize = filesize($tempfile); //File size of annotated file
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $max_mb = min($max_upload, $max_post, $memory_limit); // in mb
    $maxbytes = $max_mb*1024*1024; // in bytes

    $mdl_maxbytes = $CFG->maxbytes;
    if($mdl_maxbytes > 0)
    {
        $maxbytes = min($maxbytes, $mdl_maxbytes);
    }
    if(($fsize > 0) && ($maxbytes > 0) && ($fsize < $maxbytes))
    {
        $quba = question_engine::load_questions_usage_by_activity($usageid);       
        $qa = $quba->get_question_attempt($slot);
        if(!get_last_step_with_qt_var_and_value($qa,"-comment")){
            ///////// so that a new step gets added
            $submitteddata = array("-comment"=>"Annotated file. ");
            $markstep = $qa->get_last_step_with_qt_var("-mark");

            if ($markstep->get_state() != question_state::$unprocessed) {
                $submitteddata["-maxmark"] = $markstep->get_qt_var("-maxmark");
                $submitteddata["-mark"] = $markstep->get_qt_var("-mark");
                $submitteddata["-commentformat"] = $markstep->get_qt_var("-commentformat");
                $submitteddata["-comment"] = "Annotated file. " . $markstep->get_qt_var("-comment");
            }




            
            $quba = question_engine::load_questions_usage_by_activity($usageid);
            
            $quba->process_action($slot, $submitteddata, null);
            
                    
            $transaction = $DB->start_delegated_transaction();
            question_engine::save_questions_usage_by_activity($quba);
            $transaction->allow_commit();
                    
            $quba = question_engine::load_questions_usage_by_activity($usageid);       
            $qa = $quba->get_question_attempt($slot);
            $itemid = get_last_step_with_qt_var_and_value($qa,"-comment")->get_id();
            /////////   

            $fs = get_file_storage();
            // Prepare file record object
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

            $fs->create_file_from_pathname($fileinfo, $tempfile); 
        }
        else{
            $itemid = get_last_step_with_qt_var_and_value($qa,"-comment")->get_id();
            $fs = get_file_storage();
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

            $storedfile = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
            $storedfile->delete();
            $fs->create_file_from_pathname($fileinfo, $tempfile); 
        }
       
             
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

