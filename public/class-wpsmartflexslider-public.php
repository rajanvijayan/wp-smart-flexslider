<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://flexslider.wpsmartplugin.com/
 * @since      1.0.0
 *
 * @package    Wpsmartflexslider
 * @subpackage Wpsmartflexslider/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpsmartflexslider
 * @subpackage Wpsmartflexslider/public
 * @author     WP Smart Plugin <wpsmartplugin@gmail.com>
 */
class Wpsmartflexslider_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_shortcode('display_flexslider',  array( $this, 'display_flexslider_func') );

	}

	public function display_flexslider_func( $atts ) { ob_start();
		if( isset($atts['id']) ){
			$sliderID = $atts['id'];
		}else{
			$sliderID = NULL;
		}
		$post = get_post( $sliderID ); 
		$slides = get_post_meta( $sliderID,'slide_attachmenid', true );
		?>
		<div id="carousel-wpsmart-flexslider-<?php echo $post->post_name;?>" class="carousel slide" data-ride="carousel" data-interval="<?php echo get_post_meta( $post->ID, 'interval', true );?>" data-pause="<?php echo get_post_meta( $post->ID, 'hover_pass', true );?>">
			<?php if( get_post_meta( $post->ID, 'pager_controls', true ) == 'Yes'){?>
			<ol class="carousel-indicators">
				<?php $i = 0; foreach( $slides as $slide ){ ?>
				<li data-target="#carousel-wpsmart-flexslider-<?php echo $post->post_name;?>" data-slide-to="<?php echo $i;?>" class="<?php if( $i == 0 ){?> active <?php }?>"></li>
				<?php $i++; }?>
			</ol>
			<?php }?>
		  <div class="carousel-inner" role="listbox" >
				<?php $i = 0; foreach( $slides as $key=>$slide ){ 
				$slide_title = get_post_meta($post->ID,'flex-slide-title',true); 
				$slide_desc = get_post_meta($post->ID,'flex-slide-desc',true); 
				$slide_url = get_post_meta($post->ID,'flex-slide-url',true); ?>
				<div class="item <?php if( $i == 0 ){?> active <?php }?>">
				<?php $image_attributes = wp_get_attachment_image_src($slide,$size = 'slider');
				$target_url = get_post_meta( $post->ID, 'show_slide_url', true );
				?>
					<?php if($slide_url[$key] && $target_url == 'Yes'){ ?>
					<a href="<?php echo esc_url($slide_url[$key],array('http', 'https')); ?>" target="_blank"><?php } ?>
						<img src="<?php echo $image_attributes[0]; ?>" alt="<?php $post->post_title;?>">
					<?php if($slide_url[$key] && $target_url == 'Yes'){ ?></a><?php } ?>
					<div class="carousel-caption hidden-sm">
						<div class="wpflex-desc">
							<div class="display-sec">
								<?php if( get_post_meta( $post->ID, 'show_title', true ) == 'Yes'){?>
								<h3><?php echo sanitize_text_field($slide_title[$key]); ?></h3>
								<?php }?>
								<?php if( get_post_meta( $post->ID, 'show_description', true ) == 'Yes'){?>
								<p><?php echo sanitize_text_field($slide_desc[$key]);?></p>
								<?php }?>
							</div>
						</div>
					</div>
				</div>
				<?php $i++; }?>
		  </div>
		<?php if( get_post_meta( $post->ID, 'nav_controls', true ) == 'Yes'){ ?>
			<a class="left carousel-control"  href="#carousel-wpsmart-flexslider-<?php echo $post->post_name;?>" role="button" data-slide="prev">
				<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
				<span class="sr-only">Previous</span>
			</a>
			<a class="right carousel-control"  href="#carousel-wpsmart-flexslider-<?php echo $post->post_name;?>" role="button" data-slide="next">
				<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
				<span class="sr-only">Next</span>
			</a>
		<?php }?>
		</div>

	    <?php return ob_get_clean();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpsmartflexslider-public.css', array(), $this->version, 'all' );

		wp_enqueue_style( 'bootstrap-min-css', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpsmartflexslider-public.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( 'bootstrap-min-js', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js', array( 'jquery' ), $this->version, false );

	}

}
