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
 * OnClick handlers are defined here
 * These functions will call functions in pdfannotate.js.
 *
 * @package    qtype
 * @subpackage essayannotate
 * @copyright  2024 IIT Palakkad
 * @copyright  based on work done by Ravisha Hesh {@link https://github.com/RavishaHesh/PDFJsAnnotations/tree/master}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// This function calls PDFAnnotate function defined in pdfannotate.js
// fileurl has been assigned its correct value in annotator.php file
var pdf = new PDFAnnotate("pdf-container", fileurl, {
    onPageUpdated(page, oldData, newData) {
    },
    ready() {
    },
    scale: 1.5,
    pageImageCompression: "SLOW", // FAST, MEDIUM, SLOW(Helps to control the new PDF file size)
});

function changeActiveTool(event) {
    event.preventDefault();
    var element = $(event.target).hasClass("tool-button")
    ? $(event.target)
    : $(event.target).parents(".tool-button").first();
    $(".tool-button.active").removeClass("active");
    $(element).addClass("active");
}

function enableSelector(event) {
    event.preventDefault();    
    changeActiveTool(event);
    pdf.enableSelector();    
}

function enablePencil(event) {
    event.preventDefault();    
    changeActiveTool(event);
    pdf.enablePencil();    
}

function enableAddText(event) {
    event.preventDefault();    
    changeActiveTool(event);
    pdf.enableAddText();    
}

function enableRectangle(event) {
    event.preventDefault();
    changeActiveTool(event);
    pdf.enableRectangle();    
}

function deleteSelectedObject(event) {
    event.preventDefault();
    pdf.deleteSelectedObject();   
}

function savePDF(event) {
    event.preventDefault();    
    pdf.savePdf();        //Changes made by Asha and Parvathy: Removed a parameter of the function
}

//Change the color and font size to currently selected color and font size respectively in the UI
$(function () {
    $('.color-tool').click(function () {
        $('.color-tool.active').removeClass('active');
        $(this).addClass('active');
        color = $(this).get(0).style.backgroundColor;
        pdf.setColor(color);
    });

    $('#font-size').change(function () {
        var font_size = $(this).val();
        pdf.setFontSize(font_size);
    });
});
  
