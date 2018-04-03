$(document).ready(function(){
    // sortable rows
    $('td, th', '#dt_config_table').each(function () {
        var cell = $(this);
        cell.width(cell.width());
    });

    $('#dt_config_table tbody').sortable({
        axis: 'y',
        items: '> tr',
        forcePlaceholderSize: true,
        placeholder:'ui-state-highlight',
        handle: '.sortHandle',
        start: function (event, ui) {
            // Build a placeholder cell that spans all the cells in the row
            var cellCount = 0;
            $('td, th', ui.helper).each(function () {
                // For each TD or TH try and get it's colspan attribute, and add that or 1 to the total
                var colspan = 1;
                var colspanAttr = $(this).attr('colspan');
                if (colspanAttr > 1) {
                    colspan = colspanAttr;
                }
                cellCount += colspan;
            });

            // Add the placeholder UI - note that this is the item's content, so TD rather thanTR
            ui.placeholder.html('<td colspan="' + cellCount + '">&nbsp;</td>');
        }
    }).disableSelection();

    // select all checkboxes
    var dtAllViewSelector = $('#select_all_view');
    var dtAllCSVselector = $('#select_all_csv');
    var dtViewSelectors = $('.view_selector');
    var dtCSVselectors = $('.csv_selector');

    var setDtAllViewSelector = function(){
        if ($('.view_selector:checked').length == dtViewSelectors.length){
            dtAllViewSelector.prop('checked', true);
        }else{
            dtAllViewSelector.prop('checked', false);
        }
    };

    var setDtAllCSVselector = function(){
        if ($('.csv_selector:checked').length == dtCSVselectors.length){
            dtAllCSVselector.prop('checked', true);
        }else{
            dtAllCSVselector.prop('checked', false);
        }
    };

    setDtAllViewSelector();
    setDtAllCSVselector();

    dtAllViewSelector.on('change', function(){
        $('.view_selector').prop('checked', this.checked);
    });

    dtAllCSVselector.on('change', function(){
        $('.csv_selector').prop('checked', this.checked);
    });

    dtViewSelectors.on('change', function(){
        setDtAllViewSelector();
    });

    dtCSVselectors.on('change', function(){
        setDtAllCSVselector();
    });
});