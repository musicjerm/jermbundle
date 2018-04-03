$(document).ready(function(){
    var thisScript = $('#notification_script');
    var count = $('#notification_unread_count');
    var linkHeader = $('#notification_links_header');
    var status = thisScript.attr('data-status');
    var dataInfoTable = $('#data_info_table');

    if (status === "Unread"){
        var newCount = (count.text() - 1);
        var message_id = thisScript.attr('data-message');
        var icon = $('#notification_icon_' + message_id);
        var text = $('#notification_text_' + message_id);
        icon.removeClass("fa-envelope text-green").addClass("fa-envelope-o text-muted");
        text.addClass("text-muted");

        var plurality;

        if (newCount === 1){
            plurality = "notification"
        }else{
            plurality = "notifications"
        }

        if (newCount > 0){
            linkHeader.text("You have " + newCount + " unread " + plurality);
            count.text(newCount);
        }else{
            linkHeader.text("You have no unread notifications");
            count.text("");
        }

        // refresh dataTable
        if (dataInfoTable.length > 0){
            dataInfoTable.DataTable().ajax.reload();
        }
    }
});