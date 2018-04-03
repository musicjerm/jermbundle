$(document).ready(function(){
    var thisScript = $('#notification_script');

    // refresh page
    if (thisScript.attr('data-full-refresh')){
        location.reload();
    }

    // refresh dataTable
    if (thisScript.attr('data-refresh')){
        $('#data_info_table').DataTable().ajax.reload();
    }

    // request server clear session
    if (thisScript.attr('data-clear')){
        $.ajax({
            method: 'GET',
            url: '/session/clear',
            error: function(xhr){
                alert('Error, could not clear session. Code: '+xhr.status);
            }
        });
    }

    // fade out notification - close modal
    if (thisScript.attr('data-fade')){
        thisScript.parent().delay(1000).fadeOut(250);
        setTimeout(function(){
            thisScript.parent().modal('toggle');
        }, 1250);
    }
});