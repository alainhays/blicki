<?php
/**
 * Plugin Name: Blicki
 * Plugin URI: http://dev.wp-plugins.org/browser/blicki/
 * Author: Automattic
 * Version: ¯\_(ツ)_/¯
 * Author URI: https://automattic.com/
 * Requires at least: 4.6
 * Tested up to: 4.6
 * Text Domain: blicki
 * Domain Path: /languages/
 * License: GPL2+
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Bliki class.
 */
class Blicki {

    /**
     * Constructor.
     */
    public function __construct() {
        // Blicki constants.
        define( 'BLICKI_FILE', __FILE__ );
        define( 'BLICKI_DIR', trailingslashit( dirname( __FILE__ ) ) );

        register_activation_hook( basename( BLICKI_DIR ) . '/' . basename( BLICKI_FILE ), array( $this, 'activate' ) );

        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

		add_filter( 'the_content', array( $this, 'wrap_wiki' ) );
    }

    /**
	 * Called on plugin activation
	 */
	public function activate() {
        $this->includes();
		Blicki_CPT::register_post_types();
		flush_rewrite_rules();
	}

    /**
     * Textdomain.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'blicki', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Includes.
     */
    public function includes() {
        include_once( BLICKI_DIR . 'includes/class-blicki-cpt.php' );
        include_once( BLICKI_DIR . 'includes/class-blicki-revisions.php' );
        include_once( BLICKI_DIR . 'includes/class-blicki-diff-viewer.php' );
    }

    /**
     * Scripts.
     */
    public function scripts() {
		wp_enqueue_script( 'blicki_js', plugins_url( 'assets/js/blicki.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
		wp_enqueue_style( 'blicki_css', plugins_url( 'css/blicki.css', __FILE__ ), false );
    }

	/**
	 * Filter for 'the_content' to wrap a wiki entry in all our custom code.
	 */
	public function wrap_wiki( $content ) {
		global $post;

		if ( 'blicki' === $post->post_type ) {
			// add TOC
			$toc = "<div class='toc'></div>";

			// add editor
			$editor = $this->get_editor( $content, $post->ID );

			// construct the wrapped output here as normal
			ob_start();
			?>
			<div id='post-wrapper-<?= esc_attr( $post->ID ); ?>' class='post-wrapper' style='border: 1px solid black;'>
				<span>Content tab</span>
				<?= $toc; ?>
				<?= $content; ?>
			</div>
			<div>
				<span>Editor tab</span>
				<?= $editor; ?>
			</div>
			<?php
			return ob_get_clean();
		}

		return $content;
	}

	/**
	 * Produces the HTML for our frontend editor component.
	 */
	private function get_editor( $content, $id ) {
		// use an output buffer here because of wp_editor
		// and it lets us construct the additional fields normally
		ob_start();
		$settings = array( 'media_buttons' => false, 'quicktags' => false );
		?>
		<form class='blicki__edit'>
			<?php wp_editor( $content, 'editor' . $id, $settings ); ?>
			<div class='blicki__edit-details'>
				<label for='email<?= esc_attr( $id ); ?>'>Enter your email address:</label>
				<input type='email' name='email<?= esc_attr( $id ); ?>' placeholder='email@example.com' id='email<?= esc_attr( $id ); ?>' />
				<button type='submit' class='blicki__edit-submit'>Submit Changes</button>
				<a class='blicki__edit-cancel'>Cancel</a>
			</div>
		</form>
		<?php
		$editor = ob_get_clean();
		return $editor;
	}
}
new Blicki();
