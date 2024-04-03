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
 * Privacy Subsystem implementation for qtype_essayannotate.
 *
 * @package    qtype_essayannotate
 * @copyright  2024 IIT Palakkad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
class helper {
    public static function getCmid($attemptid) {
        global $DB;

        $sql = "SELECT cm.id AS cmid
                FROM {quiz_attempts} qa
                JOIN {course_modules} cm ON qa.quiz = cm.instance AND cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
                WHERE qa.id = :attemptid";

        $params = ['attemptid' => $attemptid];

        $result = $DB->get_record_sql($sql, $params);

        return $result;
    }

    public static function get_first_annotation_comment_step($qa) {
        foreach ($qa->get_step_iterator() as $step) {
            if ($step->has_qt_var("-comment") && !$step->has_qt_var("-mark")) {
                return $step;
            }
        }
        return new question_attempt_step_read_only();
    }
}