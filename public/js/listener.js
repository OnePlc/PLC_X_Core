$(function () {
    $('.saveForm').on('click',function() {
        // Submit Form
        $('form.plc-core-basic-form').submit();
        return false;
    });

    $('a.initExcelDump').on('click',function(e) {
        e.stopPropagation();
        e.preventDefault();
        e.stopImmediatePropagation();

        Swal.fire({
            icon: 'info',
            title: 'Starting export',
            text: 'Skeleton export started...please wait',
            showConfirmButton: false
        });

        $.post('/skeleton/export/dump',{},function(retVal) {
            Swal.close();
            Swal.fire({
                icon: 'success',
                html: retVal,
                showConfirmButton: false
            });

            return false;
        });

        return false;
    });
});