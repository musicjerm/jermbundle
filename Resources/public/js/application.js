$(document).ready(function(){
    var actionModal = $('#action_modal');
    var gitUpdateBtn = $('#git_update_btn');
    var dbUpdateBtn = $('#db_update_btn');
    var clearCacheBtn = $('#clear_cache_btn');

    //open git updater action in modal
    gitUpdateBtn.on('click', function(){
        $.ajax({
            type: 'GET',
            url: '/admin/application/git_update',
            success: function(data){
                actionModal.html(data);
            },
            error: function(xhr){
                alert('Something has broken. Error: '+xhr.status);
            }
        });
    });

    //open doctrine updater action in modal
    dbUpdateBtn.on('click', function(){
        $.ajax({
            type: 'GET',
            url: '/admin/application/db_update',
            success: function(data){
                actionModal.html(data);
            },
            error: function(xhr){
                alert('Something has broken. Error: '+xhr.status);
            }
        })
    });

    //open clear cache action in modal
    clearCacheBtn.on('click', function(){
        $.ajax({
            type: 'GET',
            url: '/admin/application/clear_cache',
            success: function(data){
                actionModal.html(data);
            },
            error: function(xhr){
                alert('Something has broken. Error: '+xhr.status);
            }
        })
    });

    //on modal close, refresh page
    actionModal.on('hidden.bs.modal', function(){
        location.reload();
    });
});