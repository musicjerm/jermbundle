$(function () {
    $('#magic_url_btn').on('click', function(){
        $('#app_config_remoteOriginUrl').val($('#app_config_configuredUrl').val());
    })
});