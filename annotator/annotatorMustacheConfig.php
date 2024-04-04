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
 * This page consists of data for annotator.mustache.
 *
 * @package    qtype
 * @subpackage essayannotate
 * @copyright  2024 IIT Palakkad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
class annotatorMustacheConfig {
    public $page_title = 'PDF Annotation And Drawing Markup Plugin Example.';
    public $bootstrap_css_url = 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css';
    public $font_awesome_css_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css';
    public $prettify_css_url = 'https://cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css';
    public $custom_css_url = './styles.css';
    public $pdfannotate_css_url = './pdfannotate.css';
    public $annotator_text;
    public $font_size;
    public $font_sizes = array(
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
    public $color_tools = array(
        array('color' => 'rgb(0, 0, 0)', 'active' => true),
        array('color' => 'rgb(251, 17, 17)'),
        array('color' => 'rgb(2, 2, 182)'),
        array('color' => 'rgb(13, 93, 13)'),
        array('color' => 'rgb(255, 255, 0)'),
    );
    public $other_tools = array(
        array('other_tools_id'=>'select', 'icon_class' => 'fa fa-hand-pointer-o', 'onclick_action' => 'enableSelector(event)', 'tooltip' => 'Select', 'selected' => true),
        array('other_tools_id'=>'pencil','icon_class' => 'fa fa-pencil', 'onclick_action' => 'enablePencil(event)', 'tooltip' => 'Pen'),
        array('other_tools_id'=>'text','icon_class' => 'fa fa-font', 'onclick_action' => 'enableAddText(event)', 'tooltip' => 'Add Text'),
        array('other_tools_id'=>'rectangle','icon_class' => 'fa fa-square-o', 'onclick_action' => 'enableRectangle(event)', 'tooltip' => 'Highlight Box'),
    );
    public $other_tools_extra ;
    public $jquery_js_url = 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js';
    public $popper_js_url = 'https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js';
    public $popper_integrity = 'sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN';
    public $popper_crossorigin = 'anonymous';
    public $bootstrap_js_url = 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js';
    public $bootstrap_integrity = 'sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV';
    public $bootstrap_crossorigin = 'anonymous';
    public $pdf_js_url = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.min.js';
    public $pdf_worker_js_url = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.worker.min.js';
    public $fabric_js_url = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/4.3.0/fabric.min.js';
    public $jspdf_js_url = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.2.0/jspdf.umd.min.js';
    public $prettify_js_url = 'https://cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js';
    public $pdfannotate_js_url = './pdfannotate.js';

    public function __construct() {
        $this->annotator_text = get_string('annotator', 'qtype_essayannotate');
        $this->font_size = get_string('font_size','qtype_essayannotate');
        $this->other_tools_extra = array(
            array('select_danger'=>true,'icon_class' => 'fa fa-trash', 'onclick_action' => 'deleteSelectedObject(event)'),
            array('select_light'=>true,'icon_class' => 'fa fa-save', 'onclick_action' => 'savePDF(event)', 'select_savenexit' => true,'save_exit' =>  get_string('save_exit','qtype_essayannotate')),
        );
    }
}

?>
