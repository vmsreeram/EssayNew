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
 * This page handles clickhandler for annotate button.
 *
 * @package    qtype
 * @subpackage essayannotate
 * @copyright  2024 IIT Palakkad
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function annotate(attemptid, slot, fileno) {
    var formData = new FormData();
    formData.append("attempt", attemptid);
    formData.append("slot", slot);
    formData.append("fileno", fileno);

    // Create a new form element dynamically
    var form = document.createElement("form");
    form.method = "post";
    form.action = "../../question/type/essayannotate/annotator/annotator.php";

    // Append form data as hidden input fields
    for (var pair of formData.entries()) {
        var input = document.createElement("input");
        input.type = "hidden";
        input.name = pair[0];
        input.value = pair[1];
        form.appendChild(input);
    }

    // Append the form to the document body
    document.body.appendChild(form);

    // Submit the form
    form.submit();

    // Remove the form from the document body after submission
    form.remove();
}