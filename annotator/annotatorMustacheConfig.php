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
 * This page consists of data for annotator.mustache. These definitions are used in annotator.php.
 *
 * @package    qtype_essayannotate
 * @copyright  2024 IIT Palakkad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Nideesh N, VM Sreeram
 */
require_once('../../../../config.php');
/**
 * Generates and returns the data for annotator.mustache.
 *
 * @return stdClass The data for annotator.mustache.
 */
function getAnnotatorMustacheConfig() {
    $annotatorMustacheConfig = new stdClass();
    $annotatorMustacheConfig->page_title = 'PDF Annotation And Drawing Markup Plugin Example.';
    $annotatorMustacheConfig->bootstrap_css_url = 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css';
    $annotatorMustacheConfig->font_awesome_css_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css';
    $annotatorMustacheConfig->prettify_css_url = 'https://cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css';
    $annotatorMustacheConfig->custom_css_url = './styles.css';
    $annotatorMustacheConfig->pdfannotate_css_url = './pdfannotate.css';

    $annotatorMustacheConfig->font_sizes = array(
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
    );
    $annotatorMustacheConfig->color_tools = array(
        array('color' => 'rgb(0, 0, 0)', 'active' => true),
        array('color' => 'rgb(251, 17, 17)'),
        array('color' => 'rgb(2, 2, 182)'),
        array('color' => 'rgb(13, 93, 13)'),
        array('color' => 'rgb(255, 255, 0)'),
    );
    $annotatorMustacheConfig->other_tools = array(
        array('other_tools_id'=>'select', 'icon_class' => 'fa fa-hand-pointer-o', 'tooltip' => 'Select', 'selected' => true),
        array('other_tools_id'=>'pencil','icon_class' => 'fa fa-pencil', 'tooltip' => 'Pen'),
        array('other_tools_id'=>'text','icon_class' => 'fa fa-font', 'tooltip' => 'Add Text'),
        array('other_tools_id'=>'rectangle','icon_class' => 'fa fa-square-o', 'tooltip' => 'Highlight Box'),
    );
    $annotatorMustacheConfig->other_tools_extra = array(
        array('select_danger'=>true,'other_tools_extra_id'=>'deletebtn','icon_class' => 'fa fa-trash'),
        array('select_light'=>true,'other_tools_extra_id'=>'savebtn','icon_class' => 'fa fa-save', 'select_savenexit' => true,'save_exit' =>  get_string('save_exit','qtype_essayannotate')),
    );

    $annotatorMustacheConfig->annotator_text = get_string('annotator', 'qtype_essayannotate');
    $annotatorMustacheConfig->font_size = get_string('font_size','qtype_essayannotate');
    return $annotatorMustacheConfig;
}

?>
