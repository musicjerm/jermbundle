$(document).ready(function(){
    var dataInfoTable = $('#data_info_table');
    var pageLengthSelector = $('#page_length_selector');
    var filtersForm = $('#standard_data_filters_form');
    var renderScript = $('#render_dt');
    var objectName = renderScript.attr('data-entity');
    var activeColumnPreset = renderScript.attr('data-column-preset');
    var jsonCols = null;
    var filtersControl = filtersForm.find('select.form-control:not(#page_length_selector), input:checkbox');
    var filtersControlText = filtersForm.find('input:text');
    var filtersControlDaterange = filtersForm.find('.input-daterange');
    var clearFiltersBtn = $('#clear_filters_btn');

    // disable default error handling - errors handled below
    $.fn.dataTable.ext.errMode = 'none';

    // initialize select2
    $('.select2').select2();

    //initial ajax call to get column layout - I know...  synchronous == bad
    $.ajax({
        async: false,
        method: 'POST',
        url: '/data/columns/'+objectName+'/'+activeColumnPreset,
        dataType: 'json',
        success: function(data){
            jsonCols = data;
        },
        error: function(xhr){
            alert('Error getting columns. Code: '+xhr.status);
        }
    });

    //set default columns and configure actions with buttons and paths
    var dColumns = jsonCols['columns'];
    var dRowKey = jsonCols['key'];
    var dSort = jsonCols['sort'];
    var dSortDir = jsonCols['sortDir'];
    var dActions = jsonCols['actionBtns'];
    var dGactions = jsonCols['groupBtns'];
    var dTooltip = jsonCols['tooltip'];
    var itemActionBtnData = [];

    //build item action buttons
    if (dActions.length > 0){
        itemActionBtnData.push({
            targets: 0,
            render: function(data, type, row){
                var columnContent = '';
                $.each(dActions, function(key, val){
                    if (val['target'] === 0){
                        if (val['method'] === 'data-href'){
                            columnContent += ' <a class="btn btn-flat btn-xs btn-default item_action_btn "'+
                                val['method']+'="'+row['dtActionCol'][key]['path']+
                                '" data-toggle="modal" title="'+val['text']+
                                '" data-target="#action_modal"';
                        }else{
                            columnContent += ' <a class="btn btn-flat btn-xs btn-default "'+
                                val['method']+'="'+row['dtActionCol'][key]['path']+
                                '" title="'+val['text']+'"';
                        }

                        if (val['_blank']){
                            columnContent += ' target="_blank"';
                        }
                        columnContent += '><i class="fa '+val['icon']+'"></i></a> ';

                    }
                });
                return columnContent;
            }
        });
    }

    //build group action check boxes
    if (dGactions > 0){
        var columnContent = '<input class="row_selector" type="checkbox" name="data_row_form[id][]">';
        itemActionBtnData.push({
            targets: -1,
            defaultContent: columnContent,
            title: '<input id="all_row_selector" type="checkbox">'
        });
    }

    //build field actions and tooltips (supports 4 object levels)
    for (var j = 0; j < dTooltip.length; ++j){
        (function(){
            var action = null;
            $.each(dActions, function(key, val){
                if (j === val['target']){
                    action = key;
                }
            });

            if (dTooltip[j] > -1 || action > -1){
                var ttTitleArr = [];
                if (dTooltip[j] > -1){
                    ttTitleArr = dColumns[dTooltip[j]]['data'].split('.');
                }
                itemActionBtnData.push({
                    targets: [j],
                    render: function(data, type, row){
                        var ttAction = '';
                        if (action !== null){
                            ttAction = row['dtActionCol'][action]['method']+'="'+row['dtActionCol'][action]['path']+'"';
                            if (row['dtActionCol'][action]['method'] === 'data-href'){
                                ttAction += ' class="item_action_btn" data-toggle="modal" data-target="#action_modal"';
                            }
                            if (row['dtActionCol'][action]['_blank']){
                                ttAction += ' target="_blank"';
                            }
                        }

                        var ttTitle = null;
                        if (row[ttTitleArr[0]] && row[ttTitleArr[0]][ttTitleArr[1]] && row[ttTitleArr[0]][ttTitleArr[1]][ttTitleArr[2]] && $.inArray(typeof row[ttTitleArr[0]][ttTitleArr[1]][ttTitleArr[2]][ttTitleArr[3]], ['string','number']) !== -1){
                            ttTitle = row[ttTitleArr[0]][ttTitleArr[1]][ttTitleArr[2]][ttTitleArr[3]];
                        }else if (row[ttTitleArr[0]] && row[ttTitleArr[0]][ttTitleArr[1]] && $.inArray(typeof row[ttTitleArr[0]][ttTitleArr[1]][ttTitleArr[2]], ['string','number']) !== -1){
                            ttTitle = row[ttTitleArr[0]][ttTitleArr[1]][ttTitleArr[2]];
                        }else if (row[ttTitleArr[0]] && $.inArray(typeof row[ttTitleArr[0]][ttTitleArr[1]], ['string','number']) !== -1){
                            ttTitle = row[ttTitleArr[0]][ttTitleArr[1]];
                        }else if ($.inArray(typeof row[ttTitleArr[0]], ['string','number']) !== -1){
                            ttTitle = row[ttTitleArr[0]];
                        }

                        var ttString = '';
                        if (ttTitle){
                            ttString = 'data-toggle="tooltip" title="'+ttTitle+'"';
                        }

                        if (data && (ttTitle || ttAction)){
                            return '<a '+ttAction+' '+ttString+'>'+data+'</a>';
                        }else{
                            return data;
                        }
                    }
                });
            }
        })();
    }

    //initial dataTable call
    dataInfoTable.DataTable({
        dom: '<"row"<"col-xs-12 col-md-5"i><"col-xs-12 col-md-7"p>><"table-responsive"t>',
        pageLength: parseInt(pageLengthSelector.val()),
        lengthChange: false,
        searching: false,
        serverSide: true,
        ajax: {
            url: '/data/query/'+objectName+'/'+activeColumnPreset,
            method: 'POST',
            //data: filtersForm.serializeArray()
            data: function(d){
                var form_data = filtersForm.serializeArray();
                $.each(form_data, function(key, val){
                    if (d[val.name]){
                        if ($.isArray(d[val.name])){
                            d[val.name].push(val.value);
                        }else{
                            d[val.name] = [d[val.name]];
                            d[val.name].push(val.value);
                        }
                    }else{
                        d[val.name] = val.value;
                    }
                });
            }
        },
        columns: dColumns,
        columnDefs: itemActionBtnData,
        rowId: dRowKey,
        order: [[dSort, dSortDir]],
        preDrawCallback: function(){
            $('#refreshBtn > i').addClass('fast-spin');
        },
        drawCallback: function(){
            $('#data_info_table_info').append('<a id="refreshBtn" class="btn btn-link"><i class="fa fa-refresh"></i></a>');
            $('.dataTables_paginate > .pagination').addClass('pagination-sm pagination-flat');
            $('#data_info_table > thead, tbody > tr').addClass('no-wrap');
            $('#data_info_table > tbody > tr').addClass('data_row');
            //$('#tLoader').html('');

            var rowSelector = $(':checkbox.row_selector');
            var allRowSelector = $('#all_row_selector');
            var batchActionBtn = $('.batch_action_btn');

            allRowSelector.prop('checked', false);

            $('.data_row').on('click', function(event){
                if (event.target.nodeName !== 'A' && event.target.nodeName !== 'I' && event.target.type !== 'checkbox'){
                    $(':checkbox', this).trigger('click');
                }
            });

            function setActionLinkState(){
                var totalActions = $('.row_selector:checked').length;
                if (totalActions > 0){
                    batchActionBtn.removeClass('disabled');
                }else{
                    batchActionBtn.addClass('disabled');
                }
            }

            rowSelector.on('change', function(){
                if (this.checked){
                    $(this).closest('tr').addClass('info');
                }else{
                    $(this).closest('tr').removeClass('info');
                }

                setActionLinkState();
            });

            allRowSelector.on('change', function(){
                rowSelector.prop('checked', this.checked);
                if (this.checked){
                    rowSelector.closest('tr').addClass('info');
                }else{
                    rowSelector.closest('tr').removeClass('info');
                }

                setActionLinkState();
            });

            var actionModal = $('#action_modal');
            var itemActionBtn = $('.item_action_btn');

            //load item actions
            itemActionBtn.on('click', function(){
                $.ajax({
                    type: 'GET',
                    url: $(this).attr('data-href'),
                    success: function(data){
                        actionModal.html(data)
                    },
                    error: function(xhr){
                        alert('Error loading action. Code: '+xhr.status);
                    }
                });
            });

            //refresh when clicking refresh btn
            $('#refreshBtn').on('click', function(){
                dataInfoDataTable.ajax.reload();
            });

            //set action link state on refresh
            setActionLinkState();

            //remove any lingering tooltips
            $('.tooltip').tooltip('destroy');

            //stop loading spinner
            $('#refreshBtn > i').removeClass('fast-spin');

            // initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    //handle errors
    dataInfoTable.on('error.dt', function(e, settings, techNote, message){
        // if error 1, reload page should log user out due to invalid json else display the error and message
        if (techNote === 1){
            console.log('User not signed in, redirecting to login page.');
            alert('Your session has expired, please sign back in.');
            location.reload();
        }else{
            alert('Error ('+techNote+'): '+message);
        }
    }).DataTable();

    //define dataTable for api use
    var dataInfoDataTable = dataInfoTable.DataTable();

    //update page length to display
    pageLengthSelector.on('change', function(){
        dataInfoDataTable.page.len(pageLengthSelector.val()).draw();
    });

    //send filter form for query on input keyup
    var keyupTimeout;
    filtersControlText.on('keyup', function(){
        clearTimeout(keyupTimeout);
        keyupTimeout = setTimeout(function(){
            dataInfoDataTable.ajax.reload();
        }, 300);
    });

    //send filter form for query on select change
    filtersControl.on('change', function(){
        dataInfoDataTable.ajax.reload();
    });

    //send filter form for query on daterange change
    filtersControlDaterange.datepicker({
        format: "yyyy-mm-dd",
        autoclose: false,
        todayHighlight: true
    }).on('changeDate', function(){
        dataInfoDataTable.ajax.reload();
    });

    //clear filters form
    clearFiltersBtn.on('click', function(){
        filtersControlText.val('');
        filtersControl.val('');
        $('.select2').select2({data: {id:null, text:null}});
        $("#standard_data_filters_form input:text").first().focus();
        dataInfoDataTable.ajax.reload();
    });

});