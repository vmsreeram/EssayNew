<?php
require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');


class behat_qtype_essaynew extends behat_base {

    /**
     * @When I annotate the pdf
     */
    public function i_annotate_the_pdf(){

        $js = "
        var pathString = 'M 100 100 L 200 200 L 300 450 L 450 450';

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

    /**
     * @Then I hover over :text
     */ 
    public function i_hover_over($text){
        $session = $this->getSession(); 
        $xpathSelector = sprintf('//button[contains(text(), "%s")]', $text);
        $element = $session->getPage()->find('xpath', $xpathSelector);
        $element->mouseOver();
    }
}
