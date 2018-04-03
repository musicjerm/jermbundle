$(document).ready(function(){
    var headActionBtn = $('.head_action_btn');
    var actionModal = $('#action_modal');

    //create loading animation when opening the action modal
    actionModal.on('shown.bs.modal',function(){
        actionModal.html(
            '<div class="modal-dialog modal-sm">'+
            '<div class="modal-content box">'+
            '<div class="box-body"><h4>Loading...</h4></div>'+
            '<div class="modalLoadingDiv overlay">'+
            '<i class="fa fa-spinner fa-pulse text-gray"></i>'+
            '</div>'+
            '</div>'+
            '</div>'
        );
        actionModal.addClass("in");
    });

    //open head action in modal
    headActionBtn.on('click', function(){
        $.ajax({
            type: 'GET',
            url: $(this).attr('data-href'),
            success: function(data){
                actionModal.html(data);
            },
            error: function(xhr){
                alert('Something has broken. Error: '+xhr.status);
            }
        });
    });

    //close modal on backspace/back button
    $(".modal").on("shown.bs.modal", function()  { // any time a modal is shown
        var urlReplace = "#" + $(this).attr('id'); // make the hash the id of the modal shown
        history.pushState(null, null, urlReplace); // push state that hash into the url
    });

    // If a pushstate has previously happened and the back button is clicked, hide any modals.
    $(window).on('popstate', function() {
        $(".modal").modal('hide');
    });
});