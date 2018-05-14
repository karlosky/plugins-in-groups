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
                list = list + '<span class="pig-reassign"><a id="post_tag-check-num-0" class="ntdelbutton" tabindex="0" data-pig-group="' + selected[i] + '" data-pig-plugin="' + plugin_file + '">X</a>&nbsp' + selected[i] + '</span>';
            }
            
            jQuery(".selected-groups-list[data-plugin-file='" + plugin_file + "']").html(list);
		});

    });
    
    /*
    * Reassign plugin from the group
    * @todo:
    * 1. Add the deleted option to the select
    */
    jQuery('.selected-groups-list').on('click', 'a', function () {
        var plugin_file= jQuery(this).attr('data-pig-plugin');
        var group= jQuery(this).attr('data-pig-group');
        var data = {
			'action': 'reassign_from_group',
			'plugin-file': plugin_file,
            'selected-group': group
		};
        
        jQuery.post(ajaxurl, data, function(response) {
            var selected = response.data['selected-groups'];
            var list = '';
            for (var i = 0, len = selected.length; i < len; i++) {
                list = list + '<span class="pig-reassign"><a id="post_tag-check-num-0" class="ntdelbutton" tabindex="0" data-pig-group="' + selected[i] + '" data-pig-plugin="' + plugin_file + '">X</a>&nbsp' + selected[i] + '</span>';
            }
            jQuery(".selected-groups-list[data-plugin-file='" + plugin_file + "']").html(list);
		});

    });
    
    /*
    * 
    *
    */
    jQuery('#pig_plugin_group').on('change', function () {
        var selected = jQuery(this).val();
        jQuery(location).attr('href','plugins.php?group=' + selected);
    });
    
    /* @todo:
    * 1. Hide the plugins that have not the current group assigned to.
    */
    
});
