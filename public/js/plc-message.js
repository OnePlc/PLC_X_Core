(function( $ ) {
    $.fn.printMessage = function( sTitle, sMessage, sType ) {
        if(sType === undefined) {
            sType = 'success';
        }
        Swal.fire({
            title: sTitle,
            text: sMessage,
            icon: sType
        });
    };
}( jQuery ));