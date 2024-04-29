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
 * @module     qtype_essayannotate/clickhandlers
 * @copyright  2024 IIT Palakkad
 * @copyright  based on work done by Ravisha Hesh {@link https://github.com/RavishaHesh/PDFJsAnnotations/tree/master}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tausif Iqbal, Vishal Rao (IIT Palakkad)
 * @updatedby  Parvathy S Kumar, Asha Jose
 * @updatedby  Nideesh N, VM Sreeram
 *             Converted into AMD format, added event listeners for all the tools,
 *             removed unused onPageUpdated function
 */


// This function calls PDFAnnotate function defined in pdfannotate.js
// fileurl has been assigned its correct value in annotator.php file
define(['jquery', 'qtype_essayannotate/pdfannotate'], function($, PDFAnnotate) {
    /**
     * Changes the active tool based on the event target.
     *
     * @param {Event} event - The event object.
     */
    function changeActiveTool(event) {
        event.preventDefault();
        var element = $(event.target).hasClass("tool-button")
        ? $(event.target)
        : $(event.target).parents(".tool-button").first();
        $(".tool-button.active").removeClass("active");
        $(element).addClass("active");
    }

    /**
     * Enables the selector tool in the PDF.
     *
     * @param {Event} event - The event object.
     * @param {PDFAnnotate} pdf - The PDFAnnotate instance.
     */
    function enableSelector(event, pdf) {
        event.preventDefault();
        changeActiveTool(event);
        pdf.enableSelector();
    }

    /**
     * Enables the pencil tool in the PDF.
     *
     * @param {Event} event - The event object.
     * @param {PDFAnnotate} pdf - The PDFAnnotate instance.
     */
    function enablePencil(event, pdf) {
        event.preventDefault();
        changeActiveTool(event);
        pdf.enablePencil();
    }

    /**
     * Enables the add text tool in the PDF.
     *
     * @param {Event} event - The event object.
     * @param {PDFAnnotate} pdf - The PDFAnnotate instance.
     */
    function enableAddText(event, pdf) {
        event.preventDefault();
        changeActiveTool(event);
        pdf.enableAddText();
    }

    /**
     * Enables the rectangle tool in the PDF.
     *
     * @param {Event} event - The event object.
     * @param {PDFAnnotate} pdf - The PDFAnnotate instance.
     */
    function enableRectangle(event, pdf) {
        event.preventDefault();
        changeActiveTool(event);
        pdf.enableRectangle();
    }

    /**
     * Deletes the selected object in the PDF.
     *
     * @param {Event} event - The event object.
     * @param {PDFAnnotate} pdf - The PDFAnnotate instance.
     */
    function deleteSelectedObject(event, pdf) {
        event.preventDefault();
        pdf.deleteSelectedObject();
    }

    /**
     * Saves the PDF.
     *
     * @param {Event} event - The event object.
     * @param {PDFAnnotate} pdf - The PDFAnnotate instance.
     */
    function savePDF(event, pdf) {
        document.getElementById('savebtn').setAttribute("disabled","true");
        event.preventDefault();
        pdf.savePdf(); // Changes made by Asha and Parvathy: Removed a parameter of the function
    }

    //Change the color and font size to currently selected color and font size respectively in the UI

    return {
        init: function(fileurl) {
            var pdf = new PDFAnnotate.PDFAnnotate("pdf-container", fileurl, {
                ready: function() {},
                scale: 1.5,
                pageImageCompression: "SLOW", // FAST, MEDIUM, SLOW(Helps to control the new PDF file size)
            });
            $(function () {
                $('.color-tool').click(function () {
                    $('.color-tool.active').removeClass('active');
                    $(this).addClass('active');
                    var color = $(this).get(0).style.backgroundColor;
                    pdf.setColor(color);
                });

                $('#font-size').change(function () {
                    var font_size = $(this).val();
                    pdf.setFontSize(font_size);
                });
            });

            const pencilButton = document.getElementById('pencil');
            pencilButton.addEventListener('click', function(event) {
                enablePencil(event, pdf);
            });
            const selectorButton = document.getElementById('select');
            selectorButton.addEventListener('click', function(event) {
                enableSelector(event, pdf);
            });
            const addTextButton = document.getElementById('text');
            addTextButton.addEventListener('click', function(event) {
                enableAddText(event, pdf);
            });
            const rectangleButton = document.getElementById('rectangle');
            rectangleButton.addEventListener('click', function(event) {
                enableRectangle(event, pdf);
            });
            const deleteButton = document.getElementById('deletebtn');
            deleteButton.addEventListener('click', function(event) {
                deleteSelectedObject(event, pdf);
            });
            const saveButton = document.getElementById('savebtn');
            saveButton.addEventListener('click', function(event) {
                savePDF(event, pdf);
            });
        }
    };
});
