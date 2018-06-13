$(document).ready(function(){
    var loadSpinner = $('#action_loading_div');
    var actionModal = $('#action_modal');
    var actionForm = $('#action_form');
    var modalRedirect = $('.modal_redirect');

    actionForm.find('input[type=text]').first().focus();

    $('.inputmask').inputmask();

    $('.select2').select2();

    $('.datepicker').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true
    });

    $('.timepicker').timepicker({
        showInputs: false
    });

    actionForm.submit(function(){
        $.ajax({
            type: 'POST',
            url: actionForm.attr('action'),
            data: new FormData(actionForm[0]),
            processData: false,
            contentType: false,
            success: function(data){
                actionModal.html(data)
            },
            error: function(xhr){
                alert('Something has broken. Error: '+xhr.status)
            }
        });
        loadSpinner.show();
        return false;
    });

    modalRedirect.on('click', function(){
        $.ajax({
            type: 'GET',
            url: $(this).attr('data-href'),
            success: function(data){
                actionModal.html(data)
            },
            error: function(xhr){
                alert('Something has broken. Error: '+xhr.status)
            }
        });
        loadSpinner.show();
    });
});