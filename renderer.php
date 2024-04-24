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
 * @package    qtype_essayannotate
 * @copyright  2024 IIT Palakkad
 * @copyright  based on work by 2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @author Nideesh N, VM Sreeram,
 * Tausif Iqbal, Vishal Rao (IIT Palakkad)
 * First version @link {https://github.com/TausifIqbal/moodle_quiz_annotator/blob/main/3.6/mod/quiz/annotator.php}
 *  Added logic to get and display Corrected Documents
 * This file is the second version, the changes from the previous version are as follows:
 *  Corrected Documents shown to students if and only if qa is graded.
 *  Followed Moodle coding conventions by adding language strings.
 *  Updated itemid as the attemptid of the first annotation step.
 *  Updated logic for checking filetype is PDF by using mimetype and extension.
 *  Show Annotate button to teachers adjacent to the file attachments.
 */

defined('MOODLE_INTERNAL') || die();
require_once('classes/helper.php');

/**
 * Generates the output for essayannotate questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayannotate_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
        global $CFG;
        $question = $qa->get_question();

        /** @var qtype_essayannotate_format_renderer_base $responseoutput */
        $responseoutput = $question->get_format_renderer($this->page);
        $responseoutput->set_displayoptions($options);

        // Answer field.
        $step = $qa->get_last_step_with_qt_var('answer');

        if (!$step->has_qt_var('answer') && empty($options->readonly)) {
            // Question has never been answered, fill it with response template.
            $step = new question_attempt_step(array('answer' => $question->responsetemplate));
        }

        if (empty($options->readonly)) {
            $answer = $responseoutput->response_area_input('answer', $qa,
                    $step, $question->responsefieldlines, $options->context);

        } else {
            $answer = $responseoutput->response_area_read_only('answer', $qa,
                    $step, $question->responsefieldlines, $options->context);
            $answer .= html_writer::nonempty_tag('p', $question->get_word_count_message_for_review($step->get_qt_data()));

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $answer .= plagiarism_get_links([
                    'context' => $options->context->id,
                    'component' => $qa->get_question()->qtype->plugin_name(),
                    'area' => $qa->get_usage_id(),
                    'itemid' => $qa->get_slot(),
                    'userid' => $step->get_user_id(),
                    'content' => $qa->get_response_summary()]);
            }
        }

        $files = '';

        $annotatedfiles = "";           // contains the HTML script corressponding to annotated files
        if ($question->attachments) {
            if (empty($options->readonly)) {
                $files = $this->files_input($qa, $question->attachments, $options);

            } else {
                $files = $this->files_read_only($qa, $options);

                // Display Corrected Documents to teachers always, but display to students only if the attempt is graded.
                if ($qa->get_state()->is_graded() || has_capability('mod/quiz:grade', $this->page->context)) {
                    $annotatedfiles = $this->feedback_files_read_only($qa, $options);
                }
            }
        }

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
                array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $answer, array('class' => 'answer'));

        // If there is a response and min/max word limit is set in the form then check the response word count.
        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                $question->get_validation_error($step->get_qt_data()), ['class' => 'validationerror']);
        }
        $result .= html_writer::tag('div', $files, array('class' => 'attachments'));

        // Displaying the Corrected Documents
        if (!empty($annotatedfiles)) {
            $result .= '<hr style="height:1px;border:none;color:#333;background-color:#333;">';
            $result .= '<p> <b>' . get_string('corrected_documents', 'qtype_essayannotate').'</b> </p>';
            $result .= html_writer::tag('div', $annotatedfiles, array('class' => 'attachments'));   // adding annotated file.
        }
        $result .= html_writer::end_tag('div');

        return $result;
    }

    /**
     * Displays any submitted feedback (annotated) files when the question is in read-only mode.
     * @author Tausif Iqbal, Vishal Rao
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function feedback_files_read_only(question_attempt $qa, question_display_options $options) {
        $contextid = $options->context->id;
        $component = 'question';
        $filearea = 'response_attachments';
        $filepath = '/';
        $itemid = helper::get_first_annotation_comment_step($qa)->get_id();
        $filenames = $this->get_filenames($qa, $options);

        $annotatedfiles = "";
        foreach ($filenames as $filename) {
            $fileurl = "";
            $fs = get_file_storage();
            $doesexists = $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);
            if ($doesexists === true) {
                $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
                $temp = file_encode_url(new moodle_url('/pluginfile.php'), '/' . implode('/', array(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $qa->get_usage_id(),
                    $qa->get_slot(),
                    $file->get_itemid())) .
                    $file->get_filepath() . $file->get_filename(), true);
                $fileurl = $temp;
            }
            if (!empty($fileurl) && !empty($filename)) {
                $icon = $this->output->pix_icon('f/pdf', "PDF document", 'core', ['class' => 'icon']);
                $annotatedfiles .= '<p><a href="' . $fileurl . '">' . $icon . $filename  . '</a></p>';
            }
        }
        return $annotatedfiles;
    }

    /**
     * Returns filename of any attached file.
     * @author Tausif Iqbal, Vishal Rao
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function get_filenames(question_attempt $qa, question_display_options $options) {
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $names = array();
        foreach ($files as $file) {
            $temp = $qa->get_response_file_url($file);
            $url = (explode("?", $temp))[0];
            $name = (explode("/", $url));
            $name = end($name);
            $name = urldecode($name);
            // check if format is not PDF
            // then change the filename as originalFileName_topdf.pdf
            $tempname = explode('.', $name);
            $mime = explode(' ', get_mimetype_description($file))[0];
            if ($mime != "PDF" && $tempname[1] != "pdf") {
                $name = ($tempname)[0] . "_topdf.pdf";
            }
            $names[] = $name;
        }
        return $names;
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        global $CFG;
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $filelist = [];

        $step = $qa->get_last_step_with_qt_var('attachments');

        $filenum = 0;
        $attemptid = optional_param('attempt', null, PARAM_INT);
        $slot = optional_param('slot', null, PARAM_INT);
        $this->page->requires->js_call_amd('qtype_essayannotate/annotatebutton', 'init', [$attemptid, $slot]);
        foreach ($files as $file) {
            $filenum++;
            $out = html_writer::link($qa->get_response_file_url($file),
                $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file),
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename()));

            // Display Annotate button to teachers only in comment.php and not in review.php
            if (has_capability('mod/quiz:grade', $this->page->context) &&
                $options->manualcomment == question_display_options::EDITABLE) {
                $annotate = get_string('annotate_button_label', 'qtype_essayannotate');

                $out .= '<button type="button" name = ' .
                    $filenum . ' class="btn btn-primary annotate-btn" style="margin: 5px; padding: 4px;">' . $annotate . '</button>';
            }
            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $out .= plagiarism_get_links([
                    'context' => $options->context->id,
                    'component' => $qa->get_question()->qtype->plugin_name(),
                    'area' => $qa->get_usage_id(),
                    'itemid' => $qa->get_slot(),
                    'userid' => $step->get_user_id(),
                    'file' => $file]);
            }
            $filelist[] = html_writer::tag('li', $out, ['class' => 'mb-2']);
        }

        $labelbyid = $qa->get_qt_field_name('attachments') . '_label';

        $fileslabel = $options->add_question_identifier_to_label(get_string('answerfiles', 'qtype_essayannotate'));
        $output = html_writer::tag('h4', $fileslabel, ['id' => $labelbyid, 'class' => 'sr-only']);
        $output .= html_writer::tag('ul', implode($filelist), [
            'aria-labelledby' => $labelbyid,
            'class' => 'list-unstyled m-0',
        ]);
        return $output;
    }

    /**
     * Displays the input control for when the student should upload a single file.
     * @param question_attempt $qa the question attempt to display.
     * @param int $numallowed the maximum number of attachments allowed. -1 = unlimited.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_input(question_attempt $qa, $numallowed,
            question_display_options $options) {
        global $CFG, $COURSE;
        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        $pickeroptions = new stdClass();
        $pickeroptions->mainfile = null;
        $pickeroptions->maxfiles = $numallowed;
        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid(
                'attachments', $options->context->id);
        $pickeroptions->context = $options->context;
        $pickeroptions->return_types = FILE_INTERNAL | FILE_CONTROLLED_LINK;

        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid(
                'attachments', $options->context->id);
        $pickeroptions->accepted_types = $qa->get_question()->filetypeslist;

        $fm = new form_filemanager($pickeroptions);
        $fm->options->maxbytes = get_user_max_upload_file_size(
            $this->page->context,
            $CFG->maxbytes,
            $COURSE->maxbytes,
            $qa->get_question()->maxbytes
        );
        $filesrenderer = $this->page->get_renderer('core', 'files');

        $text = '';
        if (!empty($qa->get_question()->filetypeslist)) {
            $text = html_writer::tag('p', get_string('acceptedfiletypes', 'qtype_essayannotate'));
            $filetypesutil = new \core_form\filetypes_util();
            $filetypes = $qa->get_question()->filetypeslist;
            $filetypedescriptions = $filetypesutil->describe_file_types($filetypes);
            $text .= $this->render_from_template('core_form/filetypes-descriptions', $filetypedescriptions);
        }

        $output = html_writer::start_tag('fieldset');
        $fileslabel = $options->add_question_identifier_to_label(get_string('answerfiles', 'qtype_essayannotate'));
        $output .= html_writer::tag('legend', $fileslabel, ['class' => 'sr-only']);
        $output .= $filesrenderer->render($fm);
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $qa->get_qt_field_name('attachments'),
            'value' => $pickeroptions->itemid,
        ]);
        $output .= $text;
        $output .= html_writer::end_tag('fieldset');

        return $output;
    }

    public function manual_comment(question_attempt $qa, question_display_options $options) {
        if ($options->manualcomment != question_display_options::EDITABLE) {
            return '';
        }

        $question = $qa->get_question();
        return html_writer::nonempty_tag('div', $question->format_text(
                $question->graderinfo, $question->graderinfoformat, $qa, 'qtype_essayannotate',
                'graderinfo', $question->id), array('class' => 'graderinfo'));
    }
}


/**
 * A base class to abstract out the differences between different type of
 * response format.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_essayannotate_format_renderer_base extends plugin_renderer_base {

    /** @var question_display_options Question display options instance for any necessary information for rendering the question. */
    protected $displayoptions;

    /**
     * Question number setter.
     *
     * @param question_display_options $displayoptions
     */
    public function set_displayoptions(question_display_options $displayoptions): void {
        $this->displayoptions = $displayoptions;
    }

    /**
     * Render the students respone when the question is in read-only mode.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response.
     */
    public abstract function response_area_read_only($name, question_attempt $qa,
            question_attempt_step $step, $lines, $context);

    /**
     * Render the students respone when the question is in read-only mode.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response for editing.
     */
    public abstract function response_area_input($name, question_attempt $qa,
            question_attempt_step $step, $lines, $context);

    /**
     * @return string specific class name to add to the input element.
     */
    protected abstract function class_name();
}

/**
 * An essayannotate format renderer for essayannotates where the student should not enter
 * any inline response.
 *
 * @copyright  2013 Binghamton University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayannotate_format_noinline_renderer extends qtype_essayannotate_format_renderer_base {

    protected function class_name() {
        return 'qtype_essayannotate_noinline';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        return '';
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        return '';
    }

}

/**
 * An essayannotate format renderer for essayannotates where the student should use the HTML
 * editor without the file picker.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayannotate_format_editor_renderer extends qtype_essayannotate_format_renderer_base {
    protected function class_name() {
        return 'qtype_essayannotate_editor';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        $labelbyid = $qa->get_qt_field_name($name) . '_label';

        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_essayannotate'));
        $output = html_writer::tag('h4', $responselabel, ['id' => $labelbyid, 'class' => 'sr-only']);
        $output .= html_writer::tag('div', $this->prepare_response($name, $qa, $step, $context), [
            'role' => 'textbox',
            'aria-readonly' => 'true',
            'aria-labelledby' => $labelbyid,
            'class' => $this->class_name() . ' qtype_essayannotate_response readonly',
            'style' => 'min-height: ' . ($lines * 1.5) . 'em;',
        ]);
        // Height $lines * 1.5 because that is a typical line-height on web pages.
        // That seems to give results that look OK.

        return $output;
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        global $CFG;
        require_once($CFG->dirroot . '/repository/lib.php');

        $inputname = $qa->get_qt_field_name($name);
        $responseformat = $step->get_qt_var($name . 'format');
        $id = $inputname . '_id';

        $editor = editors_get_preferred_editor($responseformat);
        $strformats = format_text_menu();
        $formats = $editor->get_supported_formats();
        foreach ($formats as $fid) {
            $formats[$fid] = $strformats[$fid];
        }

        list($draftitemid, $response) = $this->prepare_response_for_editing(
                $name, $step, $context);

        $editor->set_text($response);
        $editor->use_editor($id, $this->get_editor_options($context),
                $this->get_filepicker_options($context, $draftitemid));

        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_essayannotate'));
        $output = html_writer::tag('label', $responselabel, [
            'class' => 'sr-only',
            'for' => $id,
        ]);
        $output .= html_writer::start_tag('div', array('class' =>
                $this->class_name() . ' qtype_essayannotate_response'));

        $output .= html_writer::tag('div', html_writer::tag('textarea', s($response),
                array('id' => $id, 'name' => $inputname, 'rows' => $lines, 'cols' => 60, 'class' => 'form-control')));

        $output .= html_writer::start_tag('div');
        if (count($formats) == 1) {
            reset($formats);
            $output .= html_writer::empty_tag('input', array('type' => 'hidden',
                    'name' => $inputname . 'format', 'value' => key($formats)));

        } else {
            $output .= html_writer::label(get_string('format'), 'menu' . $inputname . 'format', false);
            $output .= ' ';
            $output .= html_writer::select($formats, $inputname . 'format', $responseformat, '');
        }
        $output .= html_writer::end_tag('div');

        $output .= $this->filepicker_html($inputname, $draftitemid);

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Prepare the response for read-only display.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response($name, question_attempt $qa,
            question_attempt_step $step, $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        return format_text($step->get_qt_var($name), $step->get_qt_var($name . 'format'),
                $formatoptions);
    }

    /**
     * Prepare the response for editing.
     * @param string $name the variable name this input edits.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response_for_editing($name,
            question_attempt_step $step, $context) {
        return array(0, $step->get_qt_var($name));
    }

    /**
     * @param object $context the context the attempt belongs to.
     * @return array options for the editor.
     */
    protected function get_editor_options($context) {
        // Disable the text-editor autosave because quiz has it's own auto save function.
        return array('context' => $context, 'autosave' => false);
    }

    /**
     * @param object $context the context the attempt belongs to.
     * @param int $draftitemid draft item id.
     * @return array filepicker options for the editor.
     */
    protected function get_filepicker_options($context, $draftitemid) {
        return array('return_types'  => FILE_INTERNAL | FILE_EXTERNAL);
    }

    /**
     * @param string $inputname input field name.
     * @param int $draftitemid draft file area itemid.
     * @return string HTML for the filepicker, if used.
     */
    protected function filepicker_html($inputname, $draftitemid) {
        return '';
    }
}


/**
 * An essayannotate format renderer for essayannotates where the student should use the HTML
 * editor with the file picker.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayannotate_format_editorfilepicker_renderer extends qtype_essayannotate_format_editor_renderer {
    protected function class_name() {
        return 'qtype_essayannotate_editorfilepicker';
    }

    protected function prepare_response($name, question_attempt $qa,
            question_attempt_step $step, $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        $text = $qa->rewrite_response_pluginfile_urls($step->get_qt_var($name),
                $context->id, 'answer', $step);
        return format_text($text, $step->get_qt_var($name . 'format'), $formatoptions);
    }

    protected function prepare_response_for_editing($name,
            question_attempt_step $step, $context) {
        return $step->prepare_response_files_draft_itemid_with_text(
                $name, $context->id, $step->get_qt_var($name));
    }

    /**
     * Get editor options for question response text area.
     * @param object $context the context the attempt belongs to.
     * @return array options for the editor.
     */
    protected function get_editor_options($context) {
        return question_utils::get_editor_options($context);
    }

    /**
     * Get the options required to configure the filepicker for one of the editor
     * toolbar buttons.
     * @deprecated since 3.5
     * @param mixed $acceptedtypes array of types of '*'.
     * @param int $draftitemid the draft area item id.
     * @param object $context the context.
     * @return object the required options.
     */
    protected function specific_filepicker_options($acceptedtypes, $draftitemid, $context) {
        debugging('qtype_essayannotate_format_editorfilepicker_renderer::specific_filepicker_options() is deprecated, ' .
            'use question_utils::specific_filepicker_options() instead.', DEBUG_DEVELOPER);

        $filepickeroptions = new stdClass();
        $filepickeroptions->accepted_types = $acceptedtypes;
        $filepickeroptions->return_types = FILE_INTERNAL | FILE_EXTERNAL;
        $filepickeroptions->context = $context;
        $filepickeroptions->env = 'filepicker';

        $options = initialise_filepicker($filepickeroptions);
        $options->context = $context;
        $options->client_id = uniqid();
        $options->env = 'editor';
        $options->itemid = $draftitemid;

        return $options;
    }

    /**
     * @param object $context the context the attempt belongs to.
     * @param int $draftitemid draft item id.
     * @return array filepicker options for the editor.
     */
    protected function get_filepicker_options($context, $draftitemid) {
        return question_utils::get_filepicker_options($context, $draftitemid);
    }

    protected function filepicker_html($inputname, $draftitemid) {
        $nonjspickerurl = new moodle_url('/repository/draftfiles_manager.php', array(
            'action' => 'browse',
            'env' => 'editor',
            'itemid' => $draftitemid,
            'subdirs' => false,
            'maxfiles' => -1,
            'sesskey' => sesskey(),
        ));

        return html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => $inputname . ':itemid', 'value' => $draftitemid)) .
                html_writer::tag('noscript', html_writer::tag('div',
                    html_writer::tag('object', '', array('type' => 'text/html',
                        'data' => $nonjspickerurl, 'height' => 160, 'width' => 600,
                        'style' => 'border: 1px solid #000;'))));
    }
}


/**
 * An essayannotate format renderer for essayannotates where the student should use a plain
 * input box, but with a normal, proportional font.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayannotate_format_plain_renderer extends qtype_essayannotate_format_renderer_base {
    /**
     * @return string the HTML for the textarea.
     */
    protected function textarea($response, $lines, $attributes) {
        $attributes['class'] = $this->class_name() . ' qtype_essayannotate_response form-control';
        $attributes['rows'] = $lines;
        $attributes['cols'] = 60;
        return html_writer::tag('textarea', s($response), $attributes);
    }

    protected function class_name() {
        return 'qtype_essayannotate_plain';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        $id = $qa->get_qt_field_name($name) . '_id';

        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_essayannotate'));
        $output = html_writer::tag('label', $responselabel, ['class' => 'sr-only', 'for' => $id]);
        $output .= $this->textarea($step->get_qt_var($name), $lines, ['id' => $id, 'readonly' => 'readonly']);
        return $output;
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        $inputname = $qa->get_qt_field_name($name);
        $id = $inputname . '_id';

        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_essayannotate'));
        $output = html_writer::tag('label', $responselabel, ['class' => 'sr-only', 'for' => $id]);
        $output .= $this->textarea($step->get_qt_var($name), $lines, ['name' => $inputname, 'id' => $id]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $inputname . 'format', 'value' => FORMAT_PLAIN]);

        return $output;
    }
}


/**
 * An essayannotate format renderer for essayannotates where the student should use a plain
 * input box with a monospaced font. You might use this, for example, for a
 * question where the students should type computer code.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_essayannotate_format_monospaced_renderer extends qtype_essayannotate_format_plain_renderer {
    protected function class_name() {
        return 'qtype_essayannotate_monospaced';
    }
}
