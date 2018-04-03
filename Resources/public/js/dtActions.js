$(document).ready(function(){
    //make select2 plugin work in a modal
    $.fn.modal.Constructor.prototype.enforceFocus = function () {};

    var batchActionBtn = $('.batch_action_btn');
    var actionModal = $('#action_modal');
    var renderScript = $('#render_dt');
    var objectName = renderScript.attr('data-entity');
    var activeColumnPreset = renderScript.attr('data-column-preset');
    var activeFilterPreset = renderScript.attr('data-filter-preset');
    var filtersForm = $('#standard_data_filters_form');
    var getCSVbtn = $('#data_get_csv');
    var columnPresetSelectors = $('.column_preset_selector');
    var deletePresetBtn = $('.delete_preset_btn');
    var newFilterBtn = $('#new_filter_btn');
    var filterPresetSelectors = $('.filter_preset_selector');
    var sharePresetBox = $('.share_preset_link');
    var sharePresetCopyBtn = new Clipboard('.share_preset_copy_btn');

    //set focus on page load
    $("#standard_data_filters_form input:text").first().focus();

    //open batch action in modal
    batchActionBtn.on('click', function(){
        var selectedIds = [];
        $.each($('.row_selector:checked'), function(){
            selectedIds.push($(this).closest('tr').attr('id'));
        });

        $.ajax({
            type: 'POST',
            url: $(this).attr('data-href'),
            data: {'id': selectedIds, 'diEntity': objectName},
            success: function(data){
                actionModal.html(data);
            },
            error: function(xhr){
                alert('Something has broken. Error: '+xhr.status);
            }
        });
    });

    //prevent filters form from submitting on enter key press
    filtersForm.on('keydown', function(event){
        if (event.keyCode == 13){
            event.preventDefault();
            return false;
        }
    });

    //download csv of current filtered data
    getCSVbtn.on('click', function(){
        filtersForm.submit();
    });

    //delete preset
    deletePresetBtn.on('click', function(){
        if (confirm("Are you sure you want to delete this preset?")){
            var urlString = $(this).attr('data-href');
            $(this).closest('div.row').remove();

            $.ajax({
                type: 'GET',
                url: urlString,
                success: function(){

                },
                error: function(xhr){
                    alert('Unable to delete preset. Error: '+xhr.status);
                }
            });
        }
    });

    //new filter action
    newFilterBtn.on('click', function(){
        $.ajax({
            type: 'POST',
            url: $(this).attr('data-href'),
            data: filtersForm.serialize(),
            success: function(data){
                actionModal.html(data)
            },
            error: function(xhr){
                alert('Unable to save filter. Error: '+xhr.status)
            }
        })
    });

    //set column preset
    columnPresetSelectors.on('change', function(){
        window.location.href = '/di/'+objectName+'/'+this.value+'/'+activeFilterPreset;
    });

    //set filter preset
    filterPresetSelectors.on('change', function(){
        window.location.href = '/di/'+objectName+'/'+activeColumnPreset+'/'+this.value;
    });

    //select preset link
    sharePresetBox.on('click', function(){
        $(this).select();
    });

    //update tooltip after successful link copy
    var clickTimeout;
    sharePresetCopyBtn.on('success', function(e){
        clearTimeout(clickTimeout);

        $(e.trigger).attr('title', 'Copied!').tooltip('fixTitle').tooltip('show');
        clickTimeout = setTimeout(function(){
            $(e.trigger).attr('title', 'Copy').tooltip('fixTitle');
        }, 1000);
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