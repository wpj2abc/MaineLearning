<?php

if(is_admin()){
	add_action('init', 'wpv_filter_search_init');
	
	function wpv_filter_search_init() {
        global $pagenow;
        
        if($pagenow == 'post.php' || $pagenow == 'post-new.php'){
            add_action('wpv_add_filter_table_row', 'wpv_add_filter_search_table_row', 1, 1);
            add_filter('wpv_add_filters', 'wpv_add_filter_search', 1, 1);
        }
    }
    
    /**
     * Add a search by search filter
     * This gets added to the popup that shows the available filters.
     *
     */
    
    function wpv_add_filter_search($filters) {
        $filters['post_search'] = array('name' => 'Post search',
										'type' => 'callback',
										'callback' => 'wpv_add_search',
										'args' => array());
        return $filters;
    }

    /**
     * get the table row to add the the available filters
     *
     */
    
    function wpv_add_filter_search_table_row($view_settings) {
        if (isset($view_settings['post_search'])) {
            global $view_settings_table_row;
            $td = wpv_get_table_row_ui_post_search($view_settings_table_row, '', $view_settings);
        
            echo '<tr class="wpv_filter_row" id="wpv_filter_row_' . $view_settings_table_row . '">' . $td . '</tr>';
            
            $view_settings_table_row++;
        }
    }
    
    /**
     * get the table info for the search.
     * This is called (via ajax) when we add a post search filter
     * It's also called to display the existing post search filter.
     *
     */
    
    function wpv_get_table_row_ui_post_search($row, $selected, $view_settings = array()) {

        if (isset($view_settings['search_mode']) && is_array($view_settings['search_mode'])) {
            $view_settings['search_mode'] = $view_settings['search_mode'][0];
        }
        if (isset($_POST['search'])) {
            // coming from the add filter button
            $defaults = array('search_mode' => $_POST['mode'],
                              'post_search_value' => $_POST['search']);
            $view_settings = wp_parse_args($view_settings, $defaults);
        }
        
        $td = '';
        
        ob_start();
        wpv_add_search(array('mode' => 'edit',
                             'view_settings' => $view_settings));
        $data = ob_get_clean();
        
        $td .= '<td><img src="' . WPV_URL . '/res/img/delete.png" onclick="on_delete_wpv_filter(\'' . $row . '\')" style="cursor: pointer">';
        $td .= '<td class="wpv_td_filter">';
        $td .= "<div id=\"wpv-filter-search-show\">\n";
        $td .= wpv_get_filter_search_summary($view_settings);
        $td .= "</div>\n";
        $td .= "<div id=\"wpv-filter-search-edit\" style='background:" . WPV_EDIT_BACKGROUND . ";display:none'>\n";
        $td .= '<fieldset>';
        $td .= '<legend><strong>' . __('Post search', 'wpv-views') . ':</strong></legend>';
        $td .= '<div>' . $data . '</div>';
        $td .= '</fieldset>';
        ob_start();
        ?>
            <input class="button-primary" type="button" value="<?php echo __('OK', 'wpv-views'); ?>" name="<?php echo __('OK', 'wpv-views'); ?>" onclick="wpv_show_filter_search_edit_ok()"/>
            <input class="button-secondary" type="button" value="<?php echo __('Cancel', 'wpv-views'); ?>" name="<?php echo __('Cancel', 'wpv-views'); ?>" onclick="wpv_show_filter_search_edit_cancel()"/>
        <?php
        $td .= ob_get_clean();
        $td .= '</div></td>';
        
        return $td;
    }
    
    function wpv_get_filter_search_summary($view_settings) {
        
        ob_start();
        
        switch ($view_settings['search_mode']) {
            case 'specific':
                $term = $view_settings['post_search_value'];
                if ($term == '') {
                    $term = '<i>' . __('None set', 'wpv-view') . '</i>';
                }
                echo sprintf(__('Filter by this search term: <strong>%s</strong>.', 'wpv-view'), $term);
                break;
            
            case 'visitor':
                echo __('Show a <strong>search box</strong> for vistors.', 'wpv-view');
                break;
            
            case 'manual':
                echo __('The search box will be added <strong>manually</strong>. The search box shortcode to use is <strong>[wpv-filter-search-box]</strong>.', 'wpv-view');
                break;
        }
        ?>
        <br />
        <input class="button-secondary" type="button" value="<?php echo __('Edit', 'wpv-views'); ?>" name="<?php echo __('Edit', 'wpv-views'); ?>" onclick="wpv_show_filter_search_edit()"/>
        <?php
        
        $data = ob_get_clean();
        
        return $data;
        
    }
    
}

/**
 * Add the search filter to the filter popup.
 *
 */

function wpv_add_search($args) {
	
    $edit = isset($args['mode']) && $args['mode'] == 'edit';
    $view_settings = isset($args['view_settings']) ? $args['view_settings'] : array();
    
    $defaults = array('search_mode' => 'specific',
                      'post_search_value' => '');
    $view_settings = wp_parse_args($view_settings, $defaults);
    
    
	?>

	<div class="search-div" style="margin-left: 20px;">

        <ul>
            <?php $radio_name = $edit ? '_wpv_settings[search_mode][]' : 'post_search_mode[]' ?>
            <li>
                <?php $checked = $view_settings['search_mode'] == 'specific' ? 'checked="checked"' : ''; ?>
                <label><input type="radio" name="<?php echo $radio_name; ?>" value="specific" <?php echo $checked; ?>>&nbsp;<?php _e('Search for a specific text:', 'wpv-views'); ?></label>
                <?php $name = $edit ? '_wpv_settings[post_search_value]' : 'post_search_value' ?>
                <?php if ($edit): ?>
                    <input type="hidden" name="_wpv_settings[post_search]" value="1"/>
                <?php endif; ?>
                <input type='text' name="<?php echo $name; ?>" value="<?php echo $view_settings['post_search_value']; ?>" />
            </li>
            <li>
                <?php $checked = $view_settings['search_mode'] == 'visitor' ? 'checked="checked"' : ''; ?>
                <label><input type="radio" name="<?php echo $radio_name; ?>" value="visitor" <?php echo $checked; ?>>&nbsp;<?php _e('Add a search box for visitors', 'wpv-views'); ?></label>
            </li>
            <li>
                <?php $checked = $view_settings['search_mode'] == 'manual' ? 'checked="checked"' : ''; ?>
                <label><input type="radio" name="<?php echo $radio_name; ?>" value="manual" <?php echo $checked; ?>>&nbsp;<?php _e('I’ll add the search box to the HTML manually', 'wpv-views'); ?></label>
            </li>
        </ul>
        
	</div>

	<?php
}
