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
 * Helper functions for qtype_essayannotate plugin.
 *
 * @package    qtype_essayannotate
 * @copyright  2024 IIT Palakkad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Nideesh N, VM Sreeram
 */

/**
 * Class with helper functions for qtype_essayannotate plugin
 */
class helper {
    /**
     * Fetches course module id (cmid) associated with given attempt id from the database
     *
     * @param int $attemptid the attempt id whose cmid is to be found
     * @return $result the record containing cmid
     */
    public static function getcmid($attemptid) {
        global $DB;

        $sql = "SELECT cm.id AS cmid
                FROM {quiz_attempts} qa
                JOIN {course_modules} cm ON qa.quiz = cm.instance AND
                    cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
                WHERE qa.id = :attemptid";

        $params = ['attemptid' => $attemptid];

        $result = $DB->get_record_sql($sql, $params);

        return $result;
    }

    /**
     * Gets the step of first annotation made for the question attempt
     *
     * @param question_attempt $qa the question attempt whose first annotation step is to be found
     * @return $step the question attempt step of first annotation made for the question attempt if exists,
     *  otherwise, a readonly step is returned
     */
    public static function get_first_annotation_comment_step($qa) {
        foreach ($qa->get_step_iterator() as $step) {
            // The annotation step does not have qt_var "-mark".
            if ($step->has_qt_var("-comment") && !$step->has_qt_var("-mark")) {
                return $step;
            }
        }
        return new question_attempt_step_read_only();
    }

    /**
     * Create URL of the file based on the provided question attempt and file object.
     *
     * @param $qa The question attempt object.
     * @param $file The file object.
     * @return string The URL of the file.
     */
    public static function create_fileurl($qa, $file) {
        // Create url of this file.
        $url = file_encode_url(new moodle_url('/pluginfile.php'), '/' . implode('/', [
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $qa->get_usage_id(),
            $qa->get_slot(),
            $file->get_itemid()]) .
            $file->get_filepath() . $file->get_filename(), true);
        $url = (explode("?", $url))[0];     // Remove '?forcedownload=1' from the end of the url.
        return $url;
    }

    /**
     * Retrieves the filename and format from a given file URL.
     *
     * @param string $fileurl The URL of the file.
     * @return array An array containing the filename and format.
     */
    public static function get_filename_format($fileurl) {
        $filename = explode("/", $fileurl);
        $filename = end($filename);
        $filename = urldecode($filename);
        $format = explode(".", $filename);
        $format = end($format);
        return [$filename, $format];
    }
}
