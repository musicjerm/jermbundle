$(function(){
    let thisScript = $('#notification_script');

    // refresh page
    if (thisScript.attr('data-full-refresh')){
        location.reload();
    }

    // refresh dataTable
    if (thisScript.attr('data-refresh')){
        let dataInfoTable = $('#data_info_table');
        let apptCalendar = $('#appt_calendar');

        if (dataInfoTable.length > 0){
            dataInfoTable.DataTable().ajax.reload();
        }

        if(apptCalendar.length > 0){
            apptCalendar.fullCalendar('refetchEvents');
        }
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