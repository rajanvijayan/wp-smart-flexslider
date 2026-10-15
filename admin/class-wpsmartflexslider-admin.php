<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://flexslider.wpsmartplugin.com/
 * @since      1.0.0
 *
 * @package    Wpsmartflexslider
 * @subpackage Wpsmartflexslider/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpsmartflexslider
 * @subpackage Wpsmartflexslider/admin
 * @author     WP Smart Plugin <wpsmartplugin@gmail.com>
 */
class Wpsmartflexslider_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action( 'init', array( $this , 'wpsmartflexslider_install' ), 5 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		
		add_action('wp_ajax_nopriv_wpflexslider_ajax', array( $this, 'wpflexslider_ajax' ));
		add_action('wp_ajax_wpflexslider_ajax', array( $this, 'wpflexslider_ajax' ));
		add_action('do_meta_boxes', array( $this,'remove_thumbnail_box'));
	}
	public function remove_thumbnail_box() {
		remove_meta_box( 'postimagediv','flexslider','side' );
	}

	/**
	 * Register the post type
	 *
	 * @since    1.0.0
	 */
	public function wpsmartflexslider_install(){
		$labels = array(
			'name' => _x('Sliders', 'wpsmartflexslider'),
			'singular_name' => _x('Slider', 'wpsmartflexslider'),
			'add_new' => _x('Add New', 'wpsmartflexslider', 'wpsmartflexslider'),
			'add_new_item' => __('Add New Slider', 'wpsmartflexslider'),
			'edit_item' => __('Edit Slider', 'wpsmartflexslider'),
			'new_item' => __('New Slider', 'wpsmartflexslider'),
			'all_items' => __('All Sliders', 'wpsmartflexslider'),
			'view_item' => __('View Slider', 'wpsmartflexslider'),
			'search_items' => __('Search Sliders', 'wpsmartflexslider'),
			'not_found' =>  __('No Sliders found', 'wpsmartflexslider'),
			'not_found_in_trash' => __('No Sliders found in Trash', 'wpsmartflexslider'), 
			'parent_item_colon' => '',
			'menu_name' => __('Flex Slider', 'wpsmartflexslider')
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'menu_icon' => 'dashicons-format-gallery',
			'supports' =>array( 
				'title','thumbnail' 
			)
		); 
		register_post_type('flexslider', $args);
	}

	public function add_meta_box( $post_type ) {
	$post_types = array('flexslider');
		if ( in_array( $post_type, $post_types )) {
			add_meta_box(
				'additional_flexslider_information'
				,__( 'Slider Settings', 'wpsmartflexslider' )
				,array( $this, 'flexslider_meta_box_content' )
				,$post_type
				,'side'
				,'default'
			);
			add_meta_box(
				'flexslider_add_slide_information'
				,__( 'Add Slides', 'wpsmartflexslider' )
				,array( $this, 'flexslider_meta_box_add_slide' )
				,$post_type
				,'advanced'
				,'default'
			);
		}
	}

	public function save( $post_id ) {
		/*Select Theme options*/
		if ( ! isset( $_POST['flexslider_meta_box_add_slide_nonce'] ) )
			return $post_id;

		$nonce = $_POST['flexslider_meta_box_add_slide_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'flexslider_meta_box_add_slide' ) )
			return $post_id;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;
		if(isset($_POST['flex-slide-title'])){
			foreach($_POST['flex-slide-title'] as $flex_titles){
				$flex_slide_titles[] = sanitize_text_field($flex_titles);
			}			
			update_post_meta( $post_id, 'flex-slide-title', $flex_slide_titles );
		}
		if(isset($_POST['flex-slide-url'])){
			foreach($_POST['flex-slide-url'] as $flex_urls){
				$flex_slide_urls[] = esc_url($flex_urls,array('http', 'https'));
			}	
			update_post_meta( $post_id, 'flex-slide-url', $flex_slide_urls );
		}
		if(isset($_POST['flex-slide-desc'])){
			foreach($_POST['flex-slide-desc'] as $flex_decs){
				$flex_slide_decs[] = sanitize_text_field($flex_decs);
			}	
			update_post_meta( $post_id, 'flex-slide-desc', $flex_slide_decs );
		}
		
		/*Select Theme options*/
		
		if ( ! isset( $_POST['flexslider_meta_box_nonce'] ) )
			return $post_id;

		$nonce = $_POST['flexslider_meta_box_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'flexslider_meta_box' ) )
			return $post_id;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}
		if ( isset( $_POST['flexslider_additional_field'] ) ) {
			foreach( $_POST['flexslider_additional_field'] as $key => $value ){
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
	public function flexslider_meta_box_add_slide( $post ) { 
		wp_nonce_field( 'flexslider_meta_box_add_slide', 'flexslider_meta_box_add_slide_nonce' );
		$get_attachmentids = get_post_meta($post->ID,'slide_attachmenid',true);
		?>
		<div class="flexslider-wrap">
			<div id="flex_appenda">
				<div id="post-body-content">
					<div class="left">
						<table class="widefat sortable">
							<thead>
								<tr>
									<th style="width: 100px;">
										<h3>Slides</h3>
									</th>
									<th>
										<button type="button" data-slideid="<?php echo $post->ID; ?>" class="button alignright add-slide" id="flex_slide"><span class="dashicons dashicons-images-alt2"></span> Add Slide </button>
									</th>
								</tr>
							</thead>
							<tbody id="flex_append" class="ui-sortable">
							<?php if($get_attachmentids){
								$get_the_title = get_post_meta( $post->ID, 'flex-slide-title', true);
								$get_the_url = get_post_meta( $post->ID, 'flex-slide-url', true);
								$get_the_desc = get_post_meta( $post->ID, 'flex-slide-desc', true);
								foreach($get_attachmentids as $k => $get_attachmentid){ ?>
								<tr class="append_slide">
									<td>
										<div class="slide-thum flex-slide-image" style="background-image:url('<?php echo wp_get_attachment_url($get_attachmentid);?>');">
											<span data-delete="<?php echo $k; ?>" data-slider_id="<?php echo get_the_ID(); ?>" class="dashicons dashicons-trash delete_slide"></span>
										</div>
									</td>
									<td>
										<div class="flex-slide-inputs">
											<input type="text" name="flex-slide-title[]" class="flex-form-control" value="<?php echo $get_the_title[$k];?>" placeholder="Title" />
											<input type="text" name="flex-slide-url[]" id="meta-image" class="meta_image flex-form-control" value="<?php echo $get_the_url[$k]; ?>" placeholder="URL" />
											<textarea name="flex-slide-desc[]" class="flex-form-control" placeholder="Description" rows="4"><?php echo $get_the_desc[$k]; ?></textarea>
										</div>
									</td>
								</tr>
							<?php }}?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php		
	}
	public function flexslider_meta_box_content( $post ) {
		wp_nonce_field( 'flexslider_meta_box', 'flexslider_meta_box_nonce' ); ?>
		<div class="flexslider-wrap">
			<div class="flexslider-left">
				<p><label for="flexslider_nav"><?php _e( 'Display nav controls', 'wpsmartflexslider' );?></label></p>
				<select id="flexslider_additional_field[nav_controls]" class="ui-corner-all regular-text" name="flexslider_additional_field[nav_controls]">
		            <option <?php if( get_post_meta( $post->ID, 'nav_controls', true ) == 'Yes'){?> selected="selected"<?php }?> value="Yes">Yes</option>
		            <option <?php if( get_post_meta( $post->ID, 'nav_controls', true ) == 'No'){?> selected="selected"<?php }?> value="No">No</option>
		        </select>

		        <p><label for="flexslider_pager"><?php _e( 'Display pager controls', 'wpsmartflexslider' );?></label></p>
				<select id="flexslider_additional_field[pager_controls]" class="ui-corner-all regular-text" name="flexslider_additional_field[pager_controls]">
		            <option <?php if( get_post_meta( $post->ID, 'pager_controls', true ) == 'Yes'){?> selected="selected"<?php }?> value="Yes">Yes</option>
		            <option <?php if( get_post_meta( $post->ID, 'pager_controls', true ) == 'No'){?> selected="selected"<?php }?> value="No">No</option>
		        </select>

		        <p><label for="flexslider_pager"><?php _e( 'Slide Interval', 'wpsmartflexslider' );?></label></p>
				<select id="flexslider_additional_field[interval]" class="ui-corner-all regular-text" name="flexslider_additional_field[interval]">
					<?php for( $i = 1000; $i <= 10000; $i+=1000 ){?>
		            <option <?php if( get_post_meta( $post->ID, 'interval', true ) == $i){?> selected="selected"<?php }?> value="<?php echo $i;?>"><?php echo $i/1000;?> sec</option>
		            <?php }?>
		        </select>
				
				<p><label for="flexslider_nav"><?php _e( 'Hover pass', 'wpsmartflexslider' );?></label></p>
				<select id="flexslider_additional_field[hover_pass]" class="ui-corner-all regular-text" name="flexslider_additional_field[hover_pass]">
		            <option <?php if( get_post_meta( $post->ID, 'hover_pass', true ) == 'hover'){?> selected="selected"<?php }?> value="hover">Yes</option>
		            <option <?php if( get_post_meta( $post->ID, 'hover_pass', true ) == 'false'){?> selected="selected"<?php }?> value="false">No</option>
		        </select>

		        <p><label for="flexslider_show_title"><?php _e( 'Show Slide Title', 'wpsmartflexslider' );?></label></p>
				<select id="flexslider_additional_field[show_title]" class="ui-corner-all regular-text" name="flexslider_additional_field[show_title]">
		            <option <?php if( get_post_meta( $post->ID, 'show_title', true ) == 'Yes'){?> selected="selected"<?php }?> value="Yes">Yes</option>
		            <option <?php if( get_post_meta( $post->ID, 'show_title', true ) == 'No'){?> selected="selected"<?php }?> value="No">No</option>
		        </select>

		        <p><label for="flexslider_show_description"><?php _e( 'Show Slide Description', 'wpsmartflexslider' );?></label></p>
				<select id="flexslider_additional_field[show_description]" class="ui-corner-all regular-text" name="flexslider_additional_field[show_description]">
		            <option <?php if( get_post_meta( $post->ID, 'show_description', true ) == 'Yes'){?> selected="selected"<?php }?> value="Yes">Yes</option>
		            <option <?php if( get_post_meta( $post->ID, 'show_description', true ) == 'No'){?> selected="selected"<?php }?> value="No">No</option>
		        </select>	
			
		        <p><label for="flexslider_show_description"><?php _e( 'Add Slide URL', 'wpsmartflexslider' );?></label></p>
				<select id="flexslider_additional_field[show_slide_url]" class="ui-corner-all regular-text" name="flexslider_additional_field[show_slide_url]">
		            <option <?php if( get_post_meta( $post->ID, 'show_slide_url', true ) == 'Yes'){?> selected="selected"<?php }?> value="Yes">Yes</option>
		            <option <?php if( get_post_meta( $post->ID, 'show_slide_url', true ) == 'No'){?> selected="selected"<?php }?> value="No">No</option>
		        </select>			
			</div>
			<div class="flexslider-right">
				<p><label for="flexslider_nav"><?php _e( 'Shortcode', 'wpsmartflexslider' );?></label></p>
				<input type="text" readonly="" name="shortcode" class="ui-corner-all regular-text" value="<?php echo '[display_flexslider id='.$post->ID.']';?>" />
				<p class="no-top-margin"><small>copy and paste your posts, pages or custom post text editors</small></p>

				<p><label for="flexslider_nav"><?php _e( 'Function', 'wpsmartflexslider' );?></label></p>
				<input type="text" readonly="" name="shortcode" class="ui-corner-all regular-text" value="<?php echo '<?php display_flexslider('.$post->ID.'); ?>';?>" />
				<p class="no-top-margin"><small>copy and paste your template files</small></p>
			</div>
		</div>
        <?php
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpsmartflexslider_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpsmartflexslider_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpsmartflexslider-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpsmartflexslider_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpsmartflexslider_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpsmartflexslider-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script($this->plugin_name, 'ajax_var', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ajax-nonce')
		));	

	}
	// Slider Save Ajax
	public function wpflexslider_ajax( ) {
		if($_POST['mode'] == 'slider_save'){
			$wpflexslider_id = $_POST['slider_id'];
			$wpflexslide_ids = $_POST['selection'];
			
			$get_title = get_post_meta( $wpflexslider_id, 'flex-slide-title', true);
			$get_url = get_post_meta( $wpflexslider_id, 'flex-slide-url', true);
			$get_desc = get_post_meta( $wpflexslider_id, 'flex-slide-desc', true);
			
			$get_attachmentids = get_post_meta($wpflexslider_id,'slide_attachmenid',true);
			if($get_attachmentids){
				$merge_attachments = array_merge($get_attachmentids,$wpflexslide_ids);
				$save_slideids = update_post_meta($wpflexslider_id,'slide_attachmenid',$merge_attachments);
				
				$get_attachmentids = get_post_meta($wpflexslider_id,'slide_attachmenid',true);
				foreach($get_attachmentids as $k=>$get_attachmentid){ ?>
					<tr class="append_slide">
						<td>
							<div class="slide-thum flex-slide-image" style="background-image:url('<?php echo wp_get_attachment_url($get_attachmentid);?>');">
								<span data-delete="<?php echo $k; ?>" data-slider_id="<?php echo $wpflexslider_id; ?>" class="dashicons dashicons-trash delete_slide"></span>
							</div>
						</td>
						<td>
							<div class="flex-slide-inputs">
								<input type="text" name="flex-slide-title[]" class="flex-form-control" value="<?php echo $get_title[$k];?>" placeholder="Title" />
								<input type="text" name="flex-slide-url[]" id="meta-image" class="meta_image flex-form-control" value="<?php echo $get_url[$k];?>" placeholder="URL" />
								<textarea name="flex-slide-desc[]" class="flex-form-control" placeholder="Description" rows="4"><?php echo $get_desc[$k];?></textarea>
							</div>
						</td>
					</tr>
				<?php }
			}else{
				$save_slideids = update_post_meta($wpflexslider_id,'slide_attachmenid',$wpflexslide_ids);
				
				$get_attachmentids = get_post_meta($wpflexslider_id,'slide_attachmenid',true);
				foreach($wpflexslide_ids as $k=>$wpflexslide_id){ ?>
					<tr class="append_slide">
						<td>
							<div class="slide-thum flex-slide-image" style="background-image:url('<?php echo wp_get_attachment_url($wpflexslide_id);?>');">
								<span data-delete="<?php echo $k; ?>" data-slider_id="<?php echo $wpflexslider_id; ?>" class="delete_slide dashicons dashicons-trash"></span>
							</div>
						</td>
						<td>
							<div class="flex-slide-inputs">
								<input type="text" name="flex-slide-title[]" class="flex-form-control" value="<?php echo $get_title[$k];?>" placeholder="Title" />
								<input type="text" name="flex-slide-url[]" id="meta-image" class="meta_image flex-form-control" value="<?php echo $get_url[$k];?>" placeholder="URL" />
								<textarea name="flex-slide-desc[]" class="flex-form-control" placeholder="Description" rows="4"><?php echo $get_desc[$k];?></textarea>
							</div>
						</td>
					</tr>
				<?php }
			}
		}
		else if($_POST['mode'] == 'slide_delete'){
			$wpflexslider_id = $_POST['slider_id'];
			$wpflexslider_metakey = $_POST['attachment_key'];
			$get_attachmentids = get_post_meta($wpflexslider_id,'slide_attachmenid',true);
			$get_title = get_post_meta( $wpflexslider_id, 'flex-slide-title', true);
			$get_url = get_post_meta( $wpflexslider_id, 'flex-slide-url', true);
			$get_desc = get_post_meta( $wpflexslider_id, 'flex-slide-desc', true);
			
			//if (array_key_exists($wpflexslider_metakey,$get_attachmentids)){
			unset($get_attachmentids[$wpflexslider_metakey]);
			$reindex_ids = array_values($get_attachmentids);
			update_post_meta($wpflexslider_id,'slide_attachmenid',$reindex_ids); 				
			$get_attachmentids = get_post_meta($wpflexslider_id,'slide_attachmenid',true);
			
			unset($get_desc[$wpflexslider_metakey]);
			$reindex_desc = array_values($get_desc);
			update_post_meta($wpflexslider_id,'flex-slide-desc',$reindex_desc); 				
			$get_desc = get_post_meta($wpflexslider_id,'flex-slide-desc',true);
			
			unset($get_url[$wpflexslider_metakey]);
			$reindex_url = array_values($get_url);
			update_post_meta($wpflexslider_id,'flex-slide-url',$reindex_url); 				
			$get_url = get_post_meta($wpflexslider_id,'flex-slide-url',true);
			
			unset($get_title[$wpflexslider_metakey]);
			$reindex_title = array_values($get_title);
			update_post_meta($wpflexslider_id,'flex-slide-title',$reindex_title); 				
			$get_title = get_post_meta($wpflexslider_id,'flex-slide-title',true);
			
			foreach($get_attachmentids as $k=>$get_attachmentid){ ?>
				<tr class="append_slide">
					<td>
						<div class="slide-thum flex-slide-image" style="background-image:url('<?php echo wp_get_attachment_url($get_attachmentid);?>');">
							<span data-delete="<?php echo $k; ?>" data-slider_id="<?php echo $wpflexslider_id; ?>" class="dashicons dashicons-trash delete_slide"></span>
						</div>
					</td>
					<td>
						<div class="flex-slide-inputs">
							<input type="text" name="flex-slide-title[]" class="flex-form-control" value="<?php echo $get_title[$k];?>" placeholder="Title" />
							<input type="text" name="flex-slide-url[]" id="meta-image" class="meta_image flex-form-control" value="<?php echo $get_url[$k];?>" placeholder="URL" />
							<textarea name="flex-slide-desc[]" class="flex-form-control" placeholder="Description" rows="4"><?php echo $get_desc[$k];?></textarea>
						</div>
					</td>
				</tr>
			<?php }
			//}
		}
		die();
	}

}
