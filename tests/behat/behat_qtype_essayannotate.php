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

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');


class behat_qtype_essayannotate extends behat_base {

    /**
     * @When I annotate the pdf
     */
    public function i_annotate_the_pdf(){

        $js = "
        var pathString = 'M 100 100 Q 125 125 150 150 Q 175 175 200 200 L 200 200';

        var path = new fabric.Path(pathString, {
            fill: '', 
            stroke: 'black', 
            strokeWidth: 2,
            strokeLineCap: 'round', 
            strokeLineJoin: 'round' 
        });
        pdf.fabricObjects[0]._objects.push(path)
        pdf.fabricObjects[0].renderAll()
        ";
        $this->execute_script($js);
        sleep(3);
    }

    /**
    * @Then /^The document should open in a new tab$/
    */   
    public function document_should_open_in_new_tab()
    {
        $session     = $this->getSession();
        $windowNames = $session->getWindowNames();
        if(sizeof($windowNames) < 2){
            throw new \ErrorException("Expected to see at least 2 windows opened"); 
        }

        //You can even switch to that window
        $session->switchToWindow($windowNames[1]);
    }

    /**
    * @Then /^I switch to main window$/
    */  
    public function i_switch_to_main_window()
    {
        $session = $this->getSession();
        $windowNames = $session->getWindowNames();
        $session->switchToWindow($windowNames[0]);
    }

    /**
    * @When /^I go back to previous page$/
    */  
    public function i_go_back_to_previous_page()
    {
        $session = $this->getSession();
        $session->back();
    }

    
}
