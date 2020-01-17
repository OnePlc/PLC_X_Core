$(function () {
    $('.saveForm').on('click',function() {
        // Submit Form
        $('form.plc-core-basic-form').submit();
        return false;
    });
});