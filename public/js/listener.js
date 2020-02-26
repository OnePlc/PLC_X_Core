$(function () {
    $('.saveForm').on('click',function() {
        // Submit Form
        $('form.plc-core-basic-form').submit();
        return false;
    });

    $('.plc-user-menu').dropdown();

    $('a.initExcelDump').on('click',function(e) {
        e.stopPropagation();
        e.preventDefault();
        e.stopImmediatePropagation();

        var modBase = $(this).attr('href');

        $.post(modBase+'/export/wizard',{},function(retModal) {
            Swal.fire({
                icon: 'info',
                title: 'Export Data',
                html: retModal,
                showCancelButton: true,
                showConfirmButton: true,
                confirmButtonText: 'Export to xslx file'
            }).then((result) => {
                if (result.value) {
                    var bAddLabels = $('input[name="plc-export-addlabels"]').prop('checked');
                    var iSearchID = $('select[name="plc-export-filter"]').val();

                    Swal.fire({
                        icon: 'info',
                        title: 'Exporting data',
                        html: '<img src="/img/ajax-loader.gif" /><p>Excel export started...please wait. Depending on your request, it can take several minutes to complete</p>',
                        showConfirmButton: false
                    });

                    $.post(modBase+'/export/dump',{exportoptions:{add_labels:bAddLabels},search_id:iSearchID},function(retVal) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            html: retVal,
                            showConfirmButton: false
                        });

                        return false;
                    });
                }
            })
        });

        /**
        Swal.fire({
            icon: 'info',
            title: 'Exporting data',
            html: '<img src="/img/ajax-loader.gif" /><p>Excel export started...please wait. Depending on your request, it can take several minutes to complete</p>',
            showConfirmButton: false
        });

        $.post(modBase+'/export/dump',{},function(retVal) {
            Swal.close();
            Swal.fire({
                icon: 'success',
                html: retVal,
                showConfirmButton: false
            });

            return false;
        }); **/

        return false;
    });
});