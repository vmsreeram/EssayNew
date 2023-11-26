function annotate(attemptid, slot, fileno) {
    var formData = new FormData();
    formData.append("attempt", attemptid);
    formData.append("slot", slot);
    formData.append("fileno", fileno);

    // Create a new form element dynamically
    var form = document.createElement("form");
    form.method = "post";
    form.action = "../../question/type/essaynew/annotator/annotator.php";

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