jQuery(document).ready(function() {

    /*
    * Remove the plugin group
    */
    jQuery('#pig_remove_group').on('click', function(v) {
        if (confirm("Do you really want to remove this group?")){
            var current = jQuery('#pig_plugin_group').val();
            window.location.replace("?pig_remove_group_name=" + current);
        }
    });
    
    
    /*
    * Show group selector (it's visible at the moment)
    */
    jQuery('.pig-add-to-group').on('click', function(v) {
        v.preventDefault();
        jQuery(this).next('select').show();
    });
    
    
    /*
    * Assign plugin to the group
    * @todo:
    * 1. Remove the chosen option from the select
    */
    jQuery('.pig-select-group').on('change', function () {
        var plugin_file= jQuery(this).attr('data-plugin-file');
        var data = {
			'action': 'assign_to_group',
			'plugin-file': plugin_file,
            'selected-group': jQuery(this).val()
		};
        
        jQuery.post(ajaxurl, data, function(response) {
            var selected = response.data['selected-groups'];
            var list = '';
            for (var i = 0, len = selected.length; i < len; i++) {
                list = list + '<span><a id="post_tag-check-num-0" class="ntdelbutton" tabindex="0">X</a>&nbsp' + selected[i] + '</span>';
            }
            
            jQuery(".selected-groups-list[data-plugin-file='" + plugin_file + "']").html(list);
		});

    });
    
    
    /* @todo:
    * 1. Hide the plugins that have not the current group assigned to.
    * 2. Remove the group grom the plugin (X link).
    */
    
});
