<?php
/**
 * Plugin Name: Custom Shortcode Sidebars
 * Description: Create custom sidebars that are called via shortcodes in your post and pages.
 * Version: 1.2
 * Revision Date: August 17, 2010
 * Requires at least: WP 2.5
 * Tested up to: WP 3.0.1
 * Author: Jason Grim
 * Author URI: http://jgwebdevelopment.com
 * Plugin URI: http://jgwebdevelopment.com/plugins/custom-sidebars-shortcode
 */

// Start the domino
cwsc_register_check ();


/**
 * Checks if there are any custom sidebars saved, if so it registers them
 * 
 * @since 1.1
 * @return true
 */
function cwsc_register_check () {
	$cwsc_current_widgets = cwsc_get_sidebars ();
	
	// If there are some current ones, then loop through and register them
	if ( is_array ( $cwsc_current_widgets ) && ! empty ( $cwsc_current_widgets ) ) {
		cwsc_register_sidebars ( $cwsc_current_widgets );
	}
	
	return true;
}

/**
 * Filter function that execute on each shortcode in content
 * 
 * [mysidebar id='the-id-of-the-custom-sidebar']
 * 
 * @param array $atts
 * @since 1.2
 * @return string The widget HTML
 */
function cwsc_shortcode_filter ( $atts ) {
	extract ( shortcode_atts ( array (
		'id' => null 
	), $atts ) );
	
	if ( is_null ( $id ) ) return null;
	
	$sidebars = cwsc_get_sidebars ();
	
	foreach ($sidebars as $sidebar_id => $values) {
		if ( $values ['slug'] == $id ) {
			$sb_id = $sidebar_id;
		}
	}
	
	if ( ! isset( $sb_id ) ) return null;
	
	ob_start ();
	dynamic_sidebar ( $sb_id );
	$the_widget = ob_get_contents ();
	ob_end_clean ();
	
	return $the_widget;
}
add_shortcode ( 'mysidebar', 'cwsc_shortcode_filter' );

/**
 * Loops through all the registered side bars and registers them
 * 
 * @since 1.0
 * @return null
 */
function cwsc_register_sidebars ( $sidebars = array() ) {
	
	if ( ! is_array ( $sidebars ) || empty ( $sidebars ) ) return false;
	
	foreach ( $sidebars as $sidebar_id => $sidebar_values ) {
		register_sidebar ( array (
			'name' => $sidebar_values ['name'], 
			'id' => $sidebar_id, 
			'description' => $sidebar_values ['description'], 
			'before_widget' => $sidebar_values ['before_widget'], 
			'after_widget' => $sidebar_values ['after_widget'], 
			'before_title' => $sidebar_values ['before_title'], 
			'after_title' => $sidebar_values ['after_title'] 
		) );
	}
}

/**
 * Creates a slug friendly version of the input string
 * 
 * @param string $title
 * @since 1.0
 * @return string Conferted string to slug format
 */
function cwsc_slug_maker ( $title = null ) {
	// Only use standard characters
	$slug = preg_replace ( "/[^a-zA-Z0-9 \-]/", "", $title );
	
	// Remove all double spaces
	$slug = str_replace ( "  ", " ", $slug );
	
	// Replace double spaced with URL friendly slash
	$slug = str_replace ( " ", "-", $slug );
	
	// Make the slug all lowercase
	$slug = strtolower ( $slug );
	
	return $slug;
}

/**
 * Searched through all the sidebars and checks if the input slug is used or not
 * 
 * TODO: Set up unique slug checking on new and edited sidebars.
 * 
 * @param string $slug Slug to be checked for uniqueness
 * @return bool If the slug is unique or not
 * @since 1.1
 */
function cwsc_slug_checker ( $slug ) {
	$sidebars = cwsc_get_sidebars ();
	
	foreach ($sidebars as $sidebar_id => $values) {
		if ( $values ['slug'] == $slug ) return false;
	}
	
	return true;
}

/**
 * Saves or updates a custom sidebar
 * 
 * @param array $sidebar_info_array Array of info to be saved
 * @param string $sidebar_id ID of the sidebar to be updated
 * @since 1.1
 * @return bool If it worked or not
 */
function cwsc_save_widget ( $sidebar_info_array = array(), $sidebar_id = null ) {
	
	$sidebars = cwsc_get_sidebars ();
	
	// Sanitize input
	$sidebar_info_array = cwsc_stripslashes_deep ( $sidebar_info_array );
	
	// Genereate id or use the input one
	$new_sidebar_id = ( is_null( $sidebar_id ) ) ? 'cwsc-' . md5( microtime() ): $sidebar_id;
	
	$new_widget_values = $sidebar_info_array;
	
	// Create slug or check the input one
	$new_widget_values ['slug'] = ( $sidebar_info_array ['slug'] == '' ) ? cwsc_slug_maker ( $sidebar_info_array ['name'] ): cwsc_slug_maker ( $sidebar_info_array ['slug'] );
	
	// Add new sidebar to array
	$sidebars [$new_sidebar_id] = $new_widget_values;
	
	// Save updated array
	return update_option ( 'cwsc_widgets', $sidebars );
}

/**
 * Gets current list of sidebars
 * 
 * Output Array Format:
 * 	'sidebar-id'
 * 		'name'
 * 		'slug'
 * 		'description'
 * 		'before_widget'
 * 		'after_widget'
 * 		'before_title'
 * 		'after_title'
 * 
 * @return array List of sidebars
 * @since 1.1
 */
function cwsc_get_sidebars () {
	$sidebars = get_option ( 'cwsc_widgets', array () );
	
	return $sidebars;
}

/**
 * Deletes a sidebar.
 * 
 * @param string $sidebar_id ID of the sidebar to be removed
 * @return bool If it worked or not
 * @since 1.1
 */
function cwsc_delete_sidebar ( $sidebar_id ) {
	$sidebars = cwsc_get_sidebars ();
	
	unset ( $sidebars [ $sidebar_id ] );
	
	return update_option ( 'cwsc_widgets', $sidebars );
}

/**
 * Strip slashes of arrays
 * 
 * @param array $value Array to be deep cleaned
 * @since 1.1
 * @return array
 */
function cwsc_stripslashes_deep ( $value ) {
	$value = is_array ( $value ) ? array_map ( 'cwsc_stripslashes_deep', $value ) : stripslashes ( $value );
	
	return $value;
}

/**
 * Loops through all the sidebars and echos the table row in the admin page
 * 
 * @since 1.0
 * @return null
 */
function cwsc_loop_current_widgets () {
	$sidebars = get_option ( 'cwsc_widgets' );
	
	if ( ! is_array ( $sidebars ) || empty ( $sidebars ) ) return null;
	
	foreach ( $sidebars as $sidebar_id => $sidebar_values ) {
		
		?>
<tr class="iedit alt" id="recipe-cat-<?php echo $cat->cat_id; ?>">
	<td class="name column-name"><a href="#"> <?php echo $sidebar_values ['name']; ?> </a><br>
	<div class="row-actions"><span class="edit"><a
		href="?page=custom-widget-shortcode&cwsc-edit-widget=<?php echo $sidebar_id; ?>">Edit</a></span>
	<span class="delete"> | <a
		class="delete:the-list:link-cat-<?php echo $sidebar_id; ?> submitdelete"
		onclick="return confirm('Warning: You are about to delete the &quot;<?php echo $sidebar_values ['name']; ?>&quot; sidebar.  Are you sure?')"
		href="?page=custom-widget-shortcode&cwsc-delete-widget=<?php echo $sidebar_id; ?>">Delete
	</a> </span></div>
	</td>
	<td class="slug column-slug">[mysidebar id="<?php echo $sidebar_values ['slug']; ?>"]</td>
</tr>
<?php	
	}
}

/**
 * The add menu function thingy
 * 
 * @since 1.0
 * @return null
 */
function cwsc_register_page () {
	add_options_page ( 'Custom Sidebar Shortcode', 'Custom Sidebars', 'manage_options', 'custom-widget-shortcode', 'cwsc_page' );
}
add_action ( 'admin_menu', 'cwsc_register_page' );

/**
 * The page in the admin menu to add/edit/delete sidebars
 * 
 * @since 1.0
 * @return null
 */
function cwsc_page () {
	
	$edit_widgt_id = null;
	$action = 'add';
	
	echo '<h2>Manage Custom Sidebars</h2>';
	
	// default values
	$current_widget = array (
		'name' => null, 'description' => null, 'before_widget' => null, 'after_widget' => null, 'before_title' => null, 'after_title' => null, 'id' => null 
	);
	
	// add new widget
	if ( isset ( $_REQUEST ['cwsc-add-widget'] ) ) {
		
		if ( is_null ( $_REQUEST ['widget'] ['name'] ) || empty ( $_REQUEST ['widget'] ['name'] ) ) {
			echo '<div class="error"><p>Sidebar name is required!</p></div>';
			$current_widget = $_REQUEST ['widget'];
		} else {
			cwsc_save_widget( $_REQUEST ['widget'] );
			echo '<div class="updated"><p>Custom Sidebar Saved!</p></div>';
		}
	}
	
	// edit widget - loads widget details
	if ( isset ( $_REQUEST ['cwsc-edit-widget'] ) ) {
		
		if ( isset( $_REQUEST ['cwsc-edit-widget-id'] ) ) {
			cwsc_save_widget( $_REQUEST ['widget'], $_REQUEST ['cwsc-edit-widget-id'] );
			
			echo '<div class="updated"><p>Custom Sidebar Saved!</p></div>';
		}
		
		$sidebars = cwsc_get_sidebars();
		
		$get_sb = ( isset( $_REQUEST ['cwsc-edit-widget-id'] ) ) ? $_REQUEST ['cwsc-edit-widget-id']: $_REQUEST ['cwsc-edit-widget'];
		
		$current_widget = $sidebars [$get_sb];
		
		$edit_widgt_id = '<input type="hidden" name="cwsc-edit-widget-id" value="' . $get_sb . '" />';
		
		$action = 'edit';
	}
	
	// delete widget
	if ( isset ( $_REQUEST ['cwsc-delete-widget'] ) ) {
		cwsc_delete_sidebar( $_REQUEST ['cwsc-delete-widget'] );
		
		echo '<div class="updated"><p>Custom Sidebar Removed!</p></div>';
	}
	
	?>
<div class="wrap">
	<div id="col-container">
		<div id="col-right">
			<div class="col-wrap">
				<form action="#" method="get" id="posts-filter">
					<h3>Existing Custom Sidebars</h3>
					<table cellspacing="0" class="widefat fixed">
						<thead>
							<tr class="alt">
								<th>Name</th>
								<th>Shortcode</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th>Name</th>
								<th>Shortcode</th>
							</tr>
						</tfoot>
						<tbody>
							<?php echo cwsc_loop_current_widgets ();?>
						</tbody>
					</table>
				</form>
			</div>
		</div>
		<div id="col-left">
			<div class="col-wrap">
			<div class="form-wrap">
				<h3><?php echo ucfirst( $action ); ?> Custom Sidebar</h3>
					<form enctype="multipart/form-data" name="sc" id="sc" class="add:the-list: validate" method="post" action="?page=custom-widget-shortcode">
						<div class="form-field form-required">
							<label for="name">Sidebar Name</label>
							<input type="text" name="widget[name]" id="name" value="<?php echo $current_widget ['name']; ?>" size="40">
							<p>Title for your sidebar in the widget menu.</p>
						</div>
						
						<div class="form-field form-required">
							<label for="slug">Sidebar Slug</label>
							<input type="text" name="widget[slug]" id="slut" value="<?php echo $current_widget ['slug']; ?>" size="40">
							<p>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens. It's used for the shortcode id.</p>
						</div>
						
						<div class="form-field">
							<label for="description">Description</label>
							<input type="text" name="widget[description]" id="description" value="<?php echo $current_widget ['description']; ?>" size="40">
							<p>Describe what this custom sidebar will be used for and maybe it's placement for reference later.</p>
						</div>
						
						<div class="form-field">
							<label for="before_widget">Before Widget</label>
							<input type="text" name="widget[before_widget]" id="before_widget" value="<?php echo $current_widget ['before_widget']; ?>" size="40">
							<p>Text or HTML to be displayed before each widget in this sidebar.</p>
						</div>
						
						<div class="form-field">
							<label for="after_widget">After Widget</label>
							<input type="text" name="widget[after_widget]" id="after_widget" value="<?php echo $current_widget ['after_widget']; ?>" size="40">
							<p>Text or HTML to be displayed after each widget in this sidebar.</p>
						</div>
						
						<div class="form-field">
							<label for="before_title">Before Title</label>
							<input type="text" name="widget[before_title]" id="before_title" value="<?php echo $current_widget ['before_title']; ?>" size="40">
							<p>Text or HTML to be displayed before the titles of each widget in this sidebar.</p>
						</div>
						
						<div class="form-field">
							<label for="after_title">After Title</label>
							<input type="text" name="widget[after_title]" id="after_title" value="<?php echo $current_widget ['after_title']; ?>" size="40">
							<p>Text or HTML to be displayed after the titles of each widget in this sidebar.</p>
						</div>
						
						<?php echo $edit_widgt_id; ?>
						<p class="submit"><input type="submit" class="button" name="cwsc-<?php echo $action; ?>-widget" value="Save Custom Widget"></p>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
}