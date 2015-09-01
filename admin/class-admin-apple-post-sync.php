<?php

require_once plugin_dir_path( __FILE__ ) . 'actions/index/class-push.php';
require_once plugin_dir_path( __FILE__ ) . 'actions/index/class-delete.php';

/**
 * This class is in charge of syncing posts creation, updates and deletions
 * with Apple's News API.
 *
 * @since 0.4.0
 */
class Admin_Apple_Post_Sync {

	/**
	 * Current settings.
	 *
	 * @var array
	 * @access private
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	function __construct( $settings ) {
		$this->settings = $settings;

		// Register update hooks if needed
		if ( 'yes' == $settings->get( 'api_autosync' ) ) {
			add_action( 'publish_post', array( $this, 'do_publish' ), 10, 2 );
			add_action( 'before_delete_post', array( $this, 'do_delete' ) );
			add_filter( 'redirect_post_location', array( $this, 'do_redirect' ) );
		}
	}

	/**
	 * When a post is published, or a published post updated, trigger this
	 * function.
	 *
	 * @since 0.4.0
	 * @param int $id
	 * @param WP_Post $post
	 * @access public
	 */
	public function do_publish( $id, $post ) {
		// If the post has been marked as deleted from the API, ignore this update
		$deleted = get_post_meta( $id, 'apple_export_api_deleted', true );
		if ( $deleted ) {
			return;
		}

		$action = new Actions\Index\Push( $this->settings, $id );
		try {
			$action->perform();
		} catch ( Actions\Action_Exception $e ) {
			Admin_Apple_Notice::error( $e->getMessage() );
		}
	}

	/**
	 * When a post is deleted, remove it from Apple News.
	 *
	 * @since 0.4.0
	 * @param int $id
	 * @access public
	 */
	public function do_delete( $id ) {
		// If it does not have a remote API ID just ignore
		if ( ! get_post_meta( $id, 'apple_export_api_id', true ) ) {
			return;
		}

		$action = new Actions\Index\Delete( $this->settings, $id );
		try {
			$action->perform();
		} catch ( Actions\Action_Exception $e ) {
			Admin_Apple_Notice::error( $e->getMessage() );
		}
	}

	/**
	 * Handle redirects.
	 *
	 * @since 0.4.0
	 * @param string $location
	 * @return string
	 * @access public
	 */
	public function do_redirect( $location ) {
		if ( Admin_Apple_Notice::has_notice() ) {
			return 'admin.php?page=apple_export_index';
		}

		return $location;
	}

}
