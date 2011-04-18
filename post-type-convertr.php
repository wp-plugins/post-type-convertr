<?php
/*
Plugin Name: Post Type Convertr
Plugin URI: http://graphpaperpress.com
Version: 1.0
Author: Sanam Maharjan
Author URI: http://graphpaperpress.com
Description: Post Type Convertr is a bulk post conversion plugin for post types and taxonomies based on <a href="http://wordpress.org/extend/plugins/convert-post-types/">Convert Post Types</a> by Stephanie Leary.
License: GPL2

 Copyright 2011  Sanam Maharjan  (email : sanam@graphpaperpress.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

*/

add_action('admin_menu', 'bulk_convert_posts_add_pages');
function bulk_convert_posts_add_pages() {
	$css = add_management_page('Post Type Convertr', 'Post Type Convertr', 'manage_options', __FILE__, 'bulk_post_type_convertr_options');
	add_action("admin_head-$css", 'bulk_post_type_convertr_css');
}

function bulk_post_type_convertr_css() {
	echo '<style type="text/css">		
		p.filters select { width: 24em; margin: 1em 1em 1em 0;  }
		p.submit { clear: both; }
		div.postconvertwrap select{width:300px; }
	</style>';
}

function bulk_post_type_convertr_options() {
	global $wpdb;
	if ( current_user_can('edit_posts') && current_user_can('edit_pages') ) {  
		$hidden_field_name = 'bulk_convert_post_submit_hidden';
		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
			bulk_convert_posts();
			if((($_POST['post_type'] != "-1") && ($_POST['new_post_type'] != "-1")) || (($_POST['taxonomy_from'] != "-1") && ($_POST['taxonomy_to'] != "-1")) || (($_POST['taxonomy_two_from'] != "-1") && ($_POST['taxonomy_two_to'] != "-1"))){
		    ?>
				<div class="updated"><p><strong><?php echo 'Converted Successfully!.'; ?></strong></p></div>
			<?php }else{ ?>
				<div class="updated"><p><strong><?php echo 'Atleast one conversion must be selected.'; ?></strong></p></div>
			<?php } ?>		
		<?php } ?>
	
    <div class="postconvertwrap">   
		<form method="post">
			<h2><?php echo 'Post Type Convertr'; ?></h2>
			<input type="hidden" name="<?php echo esc_attr($hidden_field_name); ?>" value="Y">
			<p class="filters"><h4>Convert Post Type</h4>
				<?php
				$typeselectfrom = '';
				$typeselect2 = '';
				$taxoselectfrom = '';
				$taxoselect2 = '';	
				
				$post_types = get_post_types(array('public'=>true));	
				$ptypes = $wpdb->get_results("SELECT DISTINCT post_type FROM $wpdb->posts WHERE post_type!='revision' AND post_type!='reply' AND post_type!='attachment' AND post_type!='nav_menu_item';");
				foreach($ptypes as $ptype){
					$typeselectfrom .= "<option value=\"" . esc_attr($ptype->post_type) . "\">";
					$typeselectfrom .= esc_html($ptype->post_type);
					$typeselectfrom .= "</option>";
				}
				foreach ($post_types as $type) {
					$typeselect2 .= "<option value=\"" . esc_attr($type) . "\">";
					$typeselect2 .= esc_html($type);
					$typeselect2 .= "</option>";
				}
				?>
				<select name="post_type">
					<option value="-1"><?php echo "Convert from..."; ?></option>
					<?php echo $typeselectfrom; ?>
				</select>
				
				<select name="new_post_type">
					<option value="-1"><?php echo "Convert to..."; ?></option>
					<?php echo $typeselect2; ?>
				</select>	
			</p>
			<p>
				<h4>Convert Taxonomy 1</h4>
				<?php 
				$taxonomynames = $wpdb->get_results("SELECT DISTINCT taxonomy FROM $wpdb->term_taxonomy;");
				foreach($taxonomynames as $taxonomyname){
					$taxoselectfrom .= "<option value=\"" . esc_attr($taxonomyname->taxonomy) . "\">";
					$taxoselectfrom .= esc_html($taxonomyname->taxonomy);
					$taxoselectfrom .= "</option>";
					
				}
				
				$taxonomies=get_taxonomies('','names'); 
				foreach ($taxonomies as $taxonomy ) { 
					$taxoselect2 .= "<option value=\"" . esc_attr($taxonomy) . "\">";
					$taxoselect2 .= esc_html($taxonomy);
					$taxoselect2 .= "</option>";	
				}
				?>
				<select name="taxonomy_from">
					<option value="-1"><?php echo "Convert from..."; ?></option>
					<?php echo $taxoselectfrom; ?>
				</select>
				<select name="taxonomy_to">
					<option value="-1"><?php echo "Convert to..."; ?></option>
					<?php echo $taxoselect2; ?>
				</select>
			</p>

			<p>
				<h4>Convert Taxonomy 2</h4>	
				<select name="taxonomy_two_from">
					<option value="-1"><?php echo "Convert from..."; ?></option>
					<?php echo $taxoselectfrom; ?>
				</select>
				<select name="taxonomy_two_to">
					<option value="-1"><?php echo "Convert to..."; ?></option>
					<?php echo $taxoselect2; ?>
				</select>
			</p>

			<p class="submit">
				<input type="submit" name="submit" value="<?php echo 'Convert &raquo;'; ?>" />
			</p>
		</form> 
    </div>
    
<?php } // if user can
} 

function bulk_convert_posts() {
	global $wpdb, $wp_taxonomies, $wp_rewrite;
	$q = 'numberposts=-1&post_status=any&post_type='.$_POST['post_type'];	
	if(($_POST['post_type'] != "-1") && ($_POST['new_post_type'] != "-1")){
		$items = get_posts($q);
		foreach ($items as $item) {
			// Update the post into the database		
			$wpdb->update( $wpdb->posts, array( 'post_type' => $_POST['new_post_type']), array( 'ID' => $item->ID, 'post_type' => $_POST['post_type']), array( '%s' ), array( '%d', '%s' ) );
		}		
	}
	$wp_rewrite->flush_rules();
	if(($_POST['taxonomy_from'] != "-1") && ($_POST['taxonomy_to'] != "-1")){
		$wpdb->query("UPDATE wp_term_taxonomy SET taxonomy = REPLACE(taxonomy, '".$_POST['taxonomy_from']."', '".$_POST['taxonomy_to']."');");
	}
	
	if(($_POST['taxonomy_two_from'] != "-1") && ($_POST['taxonomy_two_to'] != "-1")){
		$wpdb->query("UPDATE wp_term_taxonomy SET taxonomy = REPLACE(taxonomy, '".$_POST['taxonomy_two_from']."', '".$_POST['taxonomy_two_to']."');");
	}
}
?>