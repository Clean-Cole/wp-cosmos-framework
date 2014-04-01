<?php
/**
 * Base class for registering a custom post type in WordPress.
 *
 *
 * @since 1.0
 */
class WPF_Post_Type {
	
	/**
	 * @access public
	 * @since 1.0
	 * @see WPF_Post_Type();
	 * @var string
	 */
	var $post_type = '';
	
	/**
	 * @access public
	 * @since 1.0
	 * @see WPF_Post_Type();
	 * @var array
	 */
	var $post_type_args = array();
	
	/**
	 * @access public
	 * @since 1.0
	 * @see WPF_Post_Type();
	 * @var array
	 */
	var $taxonomies = array();
	
	/**
	 * @access public
	 * @since 1.0
	 * @see WPF_Post_Type();
	 * @var array
	 */
	var $edit_columns = array();
	
	/**
	 * @access public
	 * @since 1.0
	 * @see WPF_Post_Type();
	 * @var array
	 */
	var $meta_boxes = array();
	
	/**
	 * @access public
	 * @since 1.0
	 * @see WPF_Post_Type();
	 * @var array
	 */
	var $statuses = array();
	
	/**
	 * A collection of common meta boxes included in the theme
	 */
	var $core_boxes = array('wpf_date');
	var $registered_statuses = array();
	
	var $tax_with_cpt = array();
	
	var $_title_changes = array();
	
	/**
	 * Constructor function for setting up everything we need
	 * Checks other post_types and taxonomies for conflicts and removes them if necessary
	 * 
	 * @see function wpf_register_post_type in /lib/functions/theme-functions.php
	 * @since 1.0
	 * 
	 */
	function __construct($post_type, $post_type_args=array(), $taxonomies=array(), $edit_columns=array(), $meta_boxes=array(), $statuses=array()) {
		
		if(is_array($taxonomies) && !empty($taxonomies)) {
			$all_taxes = get_taxonomies(NULL,'names');
			foreach($taxonomies as $tax => $args) {
				if(in_array($tax,$all_taxes)) {
					unset($taxonomies[$tax]);
				}
			}
			$this->taxonomies = $taxonomies;
		}
		
		if(is_array($statuses) && !empty($statuses)) {
			global $wp_post_statuses;
			foreach($statuses as $status => $args) {
				if(in_array($status, $wp_post_statuses)) {
					unset($statuses[$status]);
				}
			}
			$this->statuses = $statuses;
		}
		
		$all_types = get_post_types(NULL,'names');
		if(in_array($post_type,$all_types)) {
			$this->post_type = false;
			return;
		} else {
			$this->post_type = $post_type;
			add_action('init', array($this,'register_items'));
			add_action('save_post_'.$this->post_type, array($this, 'save_post'));
			add_action('admin_print_scripts', array($this, '_print_icon_32_css'));
			
		}
		
		if(is_array($post_type_args) && !empty($post_type_args)) {
			$this->post_type_args = $post_type_args;
		}
		
		if(is_array($edit_columns) && !empty($edit_columns)) {	
			$this->edit_columns = $edit_columns;
			add_filter('manage_edit-'.$this->post_type.'_columns', array($this, 'manage_edit_columns'));
		}
		
		if(is_array($meta_boxes) && !empty($meta_boxes) && is_admin()) {
			$this->meta_boxes = $meta_boxes;
			add_action('add_meta_boxes_'.$this->post_type, array($this, 'add_meta_boxes'));
		}
		
	}
	
	/**
	 * This function merges all our parameters and registers all post_types and taxonomies
	 * 
	 * @since 1.0
	 */
	function register_items() {
		
		if(!$this->post_type || empty($this->post_type))
			return false;

		$this->post_type_name = $name_guess = (isset($this->post_type_args['name']) ? (STRING)$this->post_type_args['name'] : ucwords(str_replace(array('-','_',),' ', $this->post_type)) );
		$this->post_type_name_plural = $name_guess_plural = (isset($this->post_type_args['plural_name']) ? (STRING)$this->post_type_args['plural_name'] : ucwords(str_replace(array('-','_',),' ', $this->post_type)).'s' );
		$this->enter_title_here = (isset($this->post_type_args['enter_title_here']) ? $this->post_type_args['enter_title_here'] : 'Ente title here' );
		$icon_check = $this->_check_for_icon('16');
		
		$default_args = array(
			'labels' => array(
				'name' => $name_guess_plural,
				'singular_name' => $name_guess,
				'add_new' => sprintf(__('Add New %s',t()),$name_guess),
				'add_new_item' => sprintf(__('Add New %s',t()),$name_guess),
				'edit_item' => sprintf(__('Edit %s',t()),$name_guess),
				'new_item' => sprintf(__('New %s',t()),$name_guess),
				'all_items' => sprintf(__('All %s',t()),$name_guess_plural),
				'view_item' => sprintf(__('View %s',t()),$name_guess),
				'search_items' => sprintf(__('Search %s',t()),$name_guess_plural),
				'not_found' =>  sprintf(__('No %s found',t()),$name_guess_plural),
				'not_found_in_trash' => sprintf(__('No %s found in Trash',t()),$name_guess_plural),
				'parent_item_colon' => '',
				'menu_name' => $name_guess_plural,
				'enter_title_here' => 'Enter title here',
			),
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array(
				'slug' => $this->post_type,
				'with_front' => false,
				'pages' => true,
				'feeds' => true
			),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array(),
			'menu_icon' => $icon_check
		);
		
		$this->post_type_args = array_replace_recursive($default_args,$this->post_type_args);
		register_post_type($this->post_type, $this->post_type_args);
		
		// Use our name guess to give a custom filter for the Enter Title Here input placeholder
		add_filter('enter_title_here', array($this, '_fix_enter_title_here'), 10, 2 );
		
		if(!empty($this->edit_columns)) {
			if($this->post_type_args['hierarchical']) {
				add_action('manage_'.$this->post_type.'_pages_custom_column', array(&$this, 'manage_custom_columns'));
			} else {
				add_action('manage_'.$this->post_type.'_posts_custom_column', array(&$this, 'manage_custom_columns'));
			}
		}
		
		if(is_array($this->taxonomies) && !empty($this->taxonomies)) {
			foreach($this->taxonomies as $tax => $tax_args) {
				$name_guess = (isset($tax_args['labels']['name']) ? (STRING)$tax_args['labels']['name'] : ucwords((STRING)$tax) );
				$name_guess_plural = (isset($tax_args['labels']['plural_name']) ? (STRING)$tax_args['labels']['plural_name'] : ucwords((STRING)$tax).'s' );
				
				$default_tax_args = array(
					'hierarchical' => true,
					'labels' => array(
					    'name' => $name_guess_plural,
						'singular_name' => $name_guess,
						'search_items' => sprintf(__('Search %s',t()),$name_guess_plural),
						'popular_items' => sprintf(__('Popular %s',t()),$name_guess_plural),
						'all_items' => sprintf(__('All %s',t()),$name_guess_plural),
						'parent_item' => null,
						'parent_item_colon' => null,
						'edit_item' => sprintf(__('Edit %s',t()),$name_guess),
						'update_item' => sprintf(__('Update %s',t()),$name_guess),
						'add_new_item' => sprintf(__('Add New %s',t()),$name_guess),
						'new_item_name' => sprintf(__('New %s Name',t()),$name_guess),
						'separate_items_with_commas' => sprintf(__('Separate %s with commas',t()),$name_guess_plural),
						'add_or_remove_items' => sprintf(__('Add or remove %s',t()),$name_guess_plural),
						'choose_from_most_used' => sprintf(__('Choose from the most used %s',t()),$name_guess_plural),
						'not_found' => sprintf(__('No %s found.',t()),$name_guess_plural),
						'menu_name' => $name_guess_plural
					),
					'public' => true,
					'show_in_nav_menus' => true,
					'show_ui' => true,
					'show_admin_column' => true,
					'update_count_callback' => '_update_post_term_count',
					'query_var' => true,
					'rewrite' => array( 'slug' => $tax, 'with_front' => false, 'hierarchical' => true ),
					//'capabilities' => array(),
					'sort' => NULL,
				);
				
				
				$tax_args = array_replace_recursive($default_tax_args, $tax_args);
				register_taxonomy( $tax, $this->post_type, $tax_args );
			}
		}

		if(is_array($this->statuses) && !empty($this->statuses)) {
			foreach($this->statuses as $status_id => $status_args) {
				
				$name_guess = (isset($args['label']) ? (STRING)$args['label'] : ucwords((STRING)$status_id) );
				
				$default_status_args = array(
					'label' => $name_guess,
					'public' => true,
					'exclude_from_search' => false,
					'show_in_admin_all_list' => true,
					'show_in_admin_status_list' => true,
					'label_count' => _n_noop( $name_guess . ' <span class="count">(%s)</span>', $name_guess . ' <span class="count">(%s)</span>')
				);
				$this->registered_statuses[$status_id] = array_replace_recursive($default_status_args, $status_args);
				register_post_status($status_id, $this->registered_statuses[$status_id]);
				
				add_action('admin_head', array(&$this, '_print_post_status_js'));
			}
		}
		if(is_array($this->meta_boxes) && !empty($this->meta_boxes)) {
			foreach($this->meta_boxes as $box_id => $params) {
				if(isset($params['use_thumbnail']) && $params['use_thumbnail']) {
					add_action('wp_ajax_set-custom-thumbnail-'.$this->post_type, array($this, '_ajax_set_custom_thumbnail'));
				}
			}
		}
		
		add_action('init', array(&$this, 'flush_rewrite_rules'), 999);
	}
	function do_term_with_cpt_link( $termlink, $term, $taxonomy ) {
		if(in_array($taxonomy, $this->tax_with_cpt)) {
			$post_type_slug = (isset($this->post_type_args['rewrite']['slug']) ? $this->post_type_args['rewrite']['slug'] : $this->post_type);
			return home_url(user_trailingslashit( "$post_type_slug/$taxonomy/$term->slug" ));
		}
		return $termlink;
	}

	function flush_rewrite_rules() {
		foreach($this->taxonomies as $tax => $tax_args) {
			if(isset($tax_args['rewrite']) && isset($tax_args['rewrite']['with_cpt']) && $tax_args['rewrite']['with_cpt']) {
				$slug = (isset($tax_args['rewrite']['slug']) ? $tax_args['rewrite']['slug'] : $tax);
				$post_type_slug = $this->post_type_args['rewrite']['slug'];
				//add_rewrite_tag('%'.$slug.'%','([^&]+)', $slug.'=');
				add_rewrite_rule(
					sprintf("^%s/%s/([^/]+)/?", $post_type_slug, $slug),
					sprintf('index.php?post_type=%s&%s=$matches[1]', $post_type_slug, $slug),
					'top'
				);
				add_filter( 'term_link', array(&$this, 'do_term_with_cpt_link'), 10, 3 );
				$this->tax_with_cpt[] = $tax;
			}
		}
		
		$last_options = get_option($this->post_type.'_post_type_args');
		if( $last_options != array($this->post_type_args, $this->taxonomies, $this->tax_with_cpt) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
			update_option($this->post_type.'_post_type_args', array($this->post_type_args, $this->taxonomies, $this->tax_with_cpt));
			//wpf_display_notice('Re-Write Rules for "'.$this->post_type.'" custom post type flushed','updated');
		}
	}
	/**
	 * A function for auto saving meta data to a custom post type
	 * Anything inside the post_type array will automatically have it's value validated esc'd
	 * and inserted into the post's meta.  Only one level of nested variables supported currently.
	 * 
	 * @example <input type="text" name="post_type[meta_key]" value="Your Value" />
	 * @example or.. <input type="checkbox" name="post_type[meta_key][nested_key] value="on" />
	 */
	function save_post($post_id) {
		
		
		if(!$post = get_post($post_id))
			return false;
		$post_type  = esc_attr($this->post_type);
		
		if(!isset($_POST[$post_type]) || !is_array($_POST[$post_type]) || empty($_POST[$post_type]))
			return;
		
		$prefix = 'ge_';
		
		foreach($_POST[$post_type] as $key => $value) {
			if(is_array($value)) {
				delete_post_meta($post->ID, $prefix.$key);
				foreach($value as $a_value) {
					if(!empty($a_value))
						add_post_meta($post->ID, $prefix.$key, esc_attr($a_value));
				}
			} else {
				update_post_meta($post->ID, $prefix.$key, esc_attr($value));
			}
		}
		
		//do_action('wpf_'.$prefix.'_save_post');
		
	}
	
	function add_meta_boxes() {
		if(!is_array($this->meta_boxes) || empty($this->meta_boxes))
			return;
		
		
		foreach($this->meta_boxes as $id => $params) {			
			if($id == 'wpf_date') {
				add_action('admin_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-ui-datepicker');"));
				add_action('admin_enqueue_scripts', create_function('', "wp_enqueue_style('jquery-ui');"));
			}
			
			$title = isset($params['title']) ? $params['title'] : $id;
			$context = isset($params['context']) ? $params['context'] : 'side';
			$priority = isset($params['priority']) ? $params['priority'] : 'default';
			
			add_meta_box($id, $title, array($this, 'metabox_display'), $this->post_type, $context, $priority);
		}
		
		$this->_change_metabox_title('postimagediv', sprintf(__('%s Featured Image',t()), $this->post_type_name));
		$this->_change_metabox_title('submitdiv', sprintf(__('Publish %s',t()),$this->post_type_name));
		
		
		if(is_array($this->_title_changes) && !empty($this->_title_changes)) {
			foreach($this->_title_changes as $change) {
				$this->_change_metabox_title($change['id'], $change['title']);
			}
		}
	}
	
	/**
	 * This function searchs the framework library for a valid metabox file to include
	 * 
	 * the metabox html id file search heirarchy inside /library/metaboxes/ would be as follows
	 * 
	 * {post_type}-{metabox_id}-{post_status}.php
	 * {post_type}-{metabox_id}.php
	 * {post_type}.php
	 * 
	 */
	function metabox_display($post, $data) {
		
		if(!isset($data['id']))
			return;
		
		
		$type = $post->post_type;
		$this->current_metabox_id = $box_id = $data['id'];
		$status = $post->post_status;
		$slug = $post->post_name;
		
		//$tips = wpf_get_tooltips();
		/*$all_custom = get_post_custom($post->ID);
		$meta = array();
		
		foreach($all_custom as $mkey => $mval) {
			$prefix = $type.'_';
			if(substr($mkey, 0, strlen($prefix)) == $prefix) {
				$new_key = substr($mkey, strlen($prefix));
				$meta[$new_key] = $mval;
			}
		}*/
		$meta = ge_post_custom($post->ID, $type);
		
		
		if(file_exists(WPF_ADMIN_METABOXES."{$type}-{$box_id}-{$slug}-{$status}.php")) {
			include(WPF_ADMIN_METABOXES."{$type}-{$box_id}-{$slug}-{$status}.php");
			return;
		}
		
		if(file_exists(WPF_ADMIN_METABOXES."{$type}-{$box_id}-{$slug}.php")) {
			include(WPF_ADMIN_METABOXES."{$type}-{$box_id}-{$slug}.php");
			return;
		}
		
		if(file_exists(WPF_ADMIN_METABOXES."{$type}-{$box_id}-{$status}.php")) {
			include(WPF_ADMIN_METABOXES."{$type}-{$box_id}-{$status}.php");
			return;
		}
		
		if(file_exists(WPF_ADMIN_METABOXES."{$type}-{$box_id}.php")) {
			include(WPF_ADMIN_METABOXES."{$type}-{$box_id}.php");
			return;
		}
		
		// Return Core Metaboxes last incase you want to override file
		if(file_exists(WPF_ADMIN_METABOXES."{$box_id}.php")) {
			include(WPF_ADMIN_METABOXES."{$box_id}.php");
			return;
		}
		
		if(file_exists(WPF_ADMIN_METABOXES."{$type}.php")) {
			include(WPF_ADMIN_METABOXES."{$type}.php");
			return;
		}
		
		echo WPF_ADMIN_METABOXES."{$type}.php";
		echo 'No Metabox Display Given';
		return;
		
	}
	
	function manage_edit_columns($columns) {
		
		if(!is_array($this->edit_columns) || empty($this->edit_columns))
			return $columns;
		
		if(isset($this->edit_columns['cb']))
			$this->edit_columns['cb'] = $columns['cb'];
		
		return $this->edit_columns;
		
	}
	
	function manage_custom_columns($column) {
		$file_path = WPF_ADMIN_COLUMNS.'/'.$column.'.php';
		if(file_exists($file_path)) {
			global $post;
			include($file_path);
		}
		
	}
	function change_metabox_title($id='', $title='') {
		if(!is_array($this->_title_changes))
			$this->_title_changes = array();
	
		if(empty($id) || empty($title))
			return;
		
		
		$this->_title_changes[] = array(
			'id' => $id,
			'title' => $title,
		);
 		
	}
	function _check_for_icon($size='16') {
		
		$icons = array(
			'ico'.$size.'-'.$this->post_type.'.gif',
			'ico'.$size.'-'.$this->post_type.'.jpeg',
			'ico'.$size.'-'.$this->post_type.'.jpg',
			'ico'.$size.'-'.$this->post_type.'.png'
		);
		foreach($icons as $icon) {
			if(file_exists(get_theme_part(WPF_ADMIN.'/images/'.$icon, 'file'))) {
				return get_theme_part(WPF_ADMIN.'/images/'.$icon, 'url');
			}
		}

		return false;

	}
	function _print_icon_32_css() {
		$icon = $this->_check_for_icon('32');
		if($icon) {
			echo '
				<style type="text/css">
					#icon-edit.icon32-posts-'.$this->post_type.' {
						background: url('.get_theme_part(THEME_IMG.'/admin/'.$icon).') no-repeat top left;
					}
				</style>
			';
		}
	}
	function _print_post_status_js() {
		global $typenow, $post, $pagenow;
		if($typenow == $this->post_type) {
			$options = '';
			foreach($this->registered_statuses as $status_id => $status_args) {
				$options .= '<option value="'.$status_id.'">'.$status_args['label'].'</option>';
			}
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function($) {
					$('#post_status').append($('<?php echo $options; ?>'));
					<?php if($post->post_status) : ?>
						$('#post_status').val('<?php echo $post->post_status; ?>');
						<?php
							$status_label = (isset($this->registered_statuses[$post->post_status]['label']) ? $this->registered_statuses[$post->post_status]['label'] : '');
							$status_button_label = (isset($this->registered_statuses[$post->post_status]['button_label']) ? $this->registered_statuses[$post->post_status]['button_label'] : 'Update');
						?>
						
						$('#post-status-display').html('<?php echo $status_label; ?>');
						$('#publish').val('<?php echo $status_button_label; ?>').attr('name','save');
					<?php endif; ?>
				});
			</script>
			<?php
		}
	}
	function get_metabox_arg($id, $key, $default='') {
		return (isset($this->meta_boxes[$id][$key]) ? $this->meta_boxes[$id][$key] : $default);
	}
	function _get_custom_thumbnail_html( $thumbnail_id = null, $post = null, $box_id = null ) {
		global $content_width, $_wp_additional_image_sizes;
		$post = get_post( $post );
		$box_id = (isset($box_id) ? $box_id : $this->current_metabox_id );
		$metabox = (isset($this->meta_boxes[$box_id]) ? $this->meta_boxes[$box_id] : false);
		if(!$metabox)
			return false;
		
		$set_thumbnail_title = $this->get_metabox_arg($box_id, 'set_thumbnail_title', 'Set Custom Image');
		$remove_thumbnail_title = $this->get_metabox_arg($box_id, 'remove_thumbnail_title', 'Remove Custom Image');
		$set_thumbnail_action = $this->get_metabox_arg($box_id, 'set_thumbnail_action', 'Choose Image');
		
		$custom_id = $this->post_type.'-'.$box_id.'-thumbnail';
		$image_size = (isset($this->meta_boxes[$box_id]['image_size']) ? $this->meta_boxes[$box_id]['image_size'] : 'post-thumbnail');
		
		$upload_iframe_src = esc_url( get_upload_iframe_src('image', $post->ID ) );
		$set_thumbnail_link = '<p class="hide-if-no-js"><a title="' . $set_thumbnail_title . '" href="%s" data-id="set-'.$custom_id.'" class="thickbox">%s</a></p>';
		$content = sprintf( $set_thumbnail_link, $upload_iframe_src, $set_thumbnail_title );
	
		if ( $thumbnail_id && get_post( $thumbnail_id ) ) {
			$old_content_width = $content_width;
			$content_width = 266;
			//if ( !isset( $_wp_additional_image_sizes[$image_size] ) )
			//	$thumbnail_html = wp_get_attachment_image( $thumbnail_id, array( $content_width, $content_width ) );
			//else
			$thumbnail = wp_get_attachment_image_src( $thumbnail_id, $image_size );
			if ( is_array($thumbnail) && !empty($thumbnail) ) {
				$thumbnail_html = '<img src="'.$thumbnail[0].'" width="100%" />';
			}
			
			if ( !empty( $thumbnail_html ) ) {
				$ajax_nonce = wp_create_nonce( 'set_'.esc_attr($this->post_type).'_'.$box_id.'-' . $post->ID );
				$content = sprintf( $set_thumbnail_link, $upload_iframe_src, $thumbnail_html );
				$content .= '<p class="hide-if-no-js"><a href="#" data-id="remove-'.$custom_id.'" onclick="GE.WPFRemoveCustomThumbnail(\'' . $ajax_nonce . '\');return false;">' . $remove_thumbnail_title . '</a></p>';
			}
			$content_width = $old_content_width;
		}
		// Add JS
		$js_id = str_replace('-','_',$box_id);
		
		$content .= "
		<script type='text/javascript'>
			jQuery(document).ready( function($) {
							
				GE.WPFRemoveCustomThumbnail = function(nonce){
					wp.media.post( 'set-custom-thumbnail-$this->post_type', {
						json:         true,
						post_id:      $('#post_ID').val(),
						box_id:       '$box_id',
						thumbnail_id: -1,
						_wpnonce:     wp.media.view.settings.post.nonce,
						cookie: encodeURIComponent(document.cookie)
					}).done( function( html ) {
						$( '.inside', '#$box_id' ).html( html );
					});
					
				};
				
				wp.media.$js_id = {
					get: function() {
						return wp.media.view.settings.post.{$js_id}Id;
					},
			
					set: function( id ) {
						var settings = wp.media.view.settings;
						
						settings.post.{$js_id}Id = id;
						
						wp.media.post( 'set-custom-thumbnail-$this->post_type', {
							json:         true,
							post_id:      settings.post.id,
							box_id:       '$box_id',
							thumbnail_id: id,
							_wpnonce:     settings.post.nonce
						}).done( function( html ) {
							$( '.inside', '#$box_id' ).html( html );
						});
					},
			
					frame: function() {
						if ( this._frame )
							return this._frame;
			
						this._frame = wp.media({
							title: '$set_thumbnail_title',
							state: 'featured-image',
							states: [ new wp.media.controller.FeaturedImage() ]
						});
			
						this._frame.on( 'toolbar:create:featured-image', function( toolbar ) {
							this.createSelectToolbar( toolbar, {
								text: '$set_thumbnail_action'
							});
						}, this._frame );
			
						this._frame.state('featured-image').on( 'select', this.select );
						return this._frame;
					},
			
					select: function() {
						var settings = wp.media.view.settings,
							selection = this.get('selection').single();
						
						// Too lazy to create a new controller at this point
						if ( ! settings.post.featuredImageId )
							return;
			
						wp.media.{$js_id}.set( selection ? selection.id : -1 );
					},
			
					init: function() {
						// Open the content media manager to the 'featured image' tab when
						// the post thumbnail is clicked.
						$('#$box_id').on( 'click', '[data-id=\"set-$custom_id\"]', function( event ) {
							event.preventDefault();
							// Stop propagation to prevent thickbox from activating.
							event.stopPropagation();
			
							wp.media.$js_id.frame().open();
			
						// Update the featured image id when the 'remove' link is clicked.
						}).on( 'click', '[data-id=\"remove-$custom_id\"]', function() {
							wp.media.view.settings.post.{$js_id}Id = -1;
						});
					}
				};
				$( wp.media.$js_id.init );
			});
		</script>
		";
		//return $content;
		return apply_filters( 'admin_post_thumbnail_html', $content, $post->ID );
		
	}
	function _ajax_set_custom_thumbnail() {
		$json = ! empty( $_REQUEST['json'] ); // New-style request
	
		$post_ID = intval( $_POST['post_id'] );
		if ( ! current_user_can( 'edit_post', $post_ID ) )
			wp_die( -1 );
		
		$box_id = esc_attr($_POST['box_id']);
		$meta_key = (isset($this->meta_boxes[$box_id]['meta_key']) ? $this->meta_boxes[$box_id]['meta_key'] : 'custom_thumbnail');
		$thumbnail_id = intval( $_POST['thumbnail_id'] );

		if ( $json )
			check_ajax_referer( "update-post_$post_ID" );
		else
			check_ajax_referer( "set_$this->post_type-$box_id-$post_ID" );

		if ( $thumbnail_id == '-1' ) {
			if ( $this->delete_custom_thumbnail( $post_ID, $meta_key ) ) {
				$return = $this->_get_custom_thumbnail_html( null, $post_ID, $box_id );
				$json ? wp_send_json_success( $return ) : wp_die( $return );
			} else {
				wp_die( 0 );
			}
		}

		if ( $this->set_custom_thumbnail( $post_ID, $thumbnail_id, $meta_key ) ) {
			$return = $this->_get_custom_thumbnail_html( $thumbnail_id, $post_ID, $box_id );
			$json ? wp_send_json_success( $return ) : wp_die( $return );
		}
		
		wp_die( 0 );
	}
	function set_custom_thumbnail( $post, $thumbnail_id, $meta_key='custom_thumbnail' ) {
		$post = get_post( $post );
		$thumbnail_id = absint( $thumbnail_id );
		if ( $post && $thumbnail_id && get_post( $thumbnail_id ) ) {
			if ( $thumbnail_html = wp_get_attachment_image( $thumbnail_id, 'thumbnail' ) ) {
				update_post_meta( $post->ID, $meta_key, $thumbnail_id );
				return true;
			} else {
				delete_post_meta( $post->ID, $meta_key );
				return true;
			}
		}
		return false;
	}
	function delete_custom_thumbnail( $post, $meta_key='custom_thumbnail' ) {
		$post = get_post( $post );
		if ( $post ) {
			delete_post_meta( $post->ID, $meta_key );
			return true;
		}
		return false;
	}
	function _fix_enter_title_here($title, $post) {
		if($post->post_type == $this->post_type) {
			return $this->enter_title_here;
		}
		return $title;
	}
	function _change_metabox_title($id='', $new_title='') {
		if(empty($id))
			return;
		global $wp_meta_boxes;
		foreach($wp_meta_boxes as $type => $locations) {
			foreach($locations as $location => $context) {
				foreach($context as $label => $boxes) {
					foreach($boxes as $box_id => $box_data) {
						if($box_id == $id && isset($wp_meta_boxes[$type][$location][$label][$box_id]))
							$wp_meta_boxes[$type][$location][$label][$box_id]['title'] = $new_title;
					}
				}
			}
		}
	}
	function _move_metabox_context($id='',$new_context='normal',$priority='core') {
		if(empty($id))
			return;
		
		global $wp_meta_boxes;
		foreach($wp_meta_boxes as $type => $locations) {
			foreach($locations as $location => $context) {
				foreach($context as $label => $boxes) {
					foreach($boxes as $box_id => $box_data) {
						if($box_id == $id && isset($wp_meta_boxes[$type][$location][$label][$box_id])) {
							$wp_meta_boxes[$type][$new_context][$priority][$box_id] = $wp_meta_boxes[$type][$location][$label][$box_id];
							unset($wp_meta_boxes[$type][$location][$label][$box_id]);
						}
					}
				}
			}
		}
	}
	function _unset_metabox($id='') {
		// This function searches through the entire meta box global to
		// remove a metabox independently of where a user might have moved it to.
		if(empty($id))
			return;
		
		global $wp_meta_boxes;
		foreach($wp_meta_boxes as $type => $locations) {
			foreach($locations as $location => $context) {
				foreach($context as $label => $boxes) {
					foreach($boxes as $box_id => $box_data) {
						if( $box_id == $id && isset($wp_meta_boxes[$type][$location][$label][$box_id])) {
							unset($wp_meta_boxes[$type][$location][$label][$box_id]);
						} else if(is_array($id) && in_array($box_id,$id)) {
							unset($wp_meta_boxes[$type][$location][$label][$box_id]);
						}
					}
				}
			}
		}
	}
}