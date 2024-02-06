@qtype @qtype_essay@_file_upload
Feature: The essay new question in a quiz can be annotated by teacher after submission of quiz by student 
    As a teacher 
    I need to use the PDF editor to annotate the submitted file for the essay new question 

    

@javascript
Scenario: Student uploads a file and teacher is able to annotate 
    Given the following "users" exist:
      | username |
      | teacher  |
      | student  |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |

    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |

    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext    | defaultmark | template |
      | Test questions   | essaynew       | TF1   | First question  | 20          | editorfilepicker |

    And the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | grade |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | 20    |

    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference | pagetypepattern | defaultregion |
      | private_files | System       | 1         | my-index        | side-post     |

    When I log in as "student"
    And I follow "Manage private files"
    And I upload "mod/assign/feedback/editpdf/tests/fixtures/testgs.pdf" file to "Files" filemanager
    And I press "Save changes"
    And I am on the "Quiz 1" "quiz activity" page
    And I press "Attempt quiz"
    And I should see "First question"
    And I should see "You can drag and drop files here to add them."
    And I click on "Add..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "testgs.pdf" "link"
    And I click on "Select this file" "button"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit" "button" in the "Submit all your answers and finish?" "dialogue"
    And I log out

    And I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    And I wait "3" seconds
    And I follow "Attempts: 1"
    And I wait "3" seconds
    And I follow "Review attempt"
    And I wait "3" seconds
    And I follow "Make comment or override mark"
    And I wait "3" seconds
    