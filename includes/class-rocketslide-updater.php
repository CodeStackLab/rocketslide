<?php
/**
 * RocketSlide GitHub Automatic Plugin Updater
 *
 * Automatically checks https://github.com/CodeStackLab/rocketslide for new commits/releases
 * and enables native 1-click updates inside WordPress Plugins Dashboard across all installed sites.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RocketSlide_Updater {

	private $file;
	private $plugin_slug;
	private $version;
	private $github_user;
	private $github_repo;
	private $github_api_result;

	public function __construct( $file, $github_user, $github_repo ) {
		$this->file        = $file;
		$this->plugin_slug = plugin_basename( $file );
		$this->version     = ROCKETSLIDE_VERSION;
		$this->github_user = $github_user;
		$this->github_repo = $github_repo;

		add_filter( 'site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_popup_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 4 );
	}

	/**
	 * Get remote plugin version info from GitHub raw main file
	 */
	private function get_remote_info() {
		if ( ! empty( $this->github_api_result ) ) {
			return $this->github_api_result;
		}

		$raw_url = sprintf( 'https://raw.githubusercontent.com/%s/%s/main/rocketslide.php', $this->github_user, $this->github_repo );
		$response = wp_remote_get( $raw_url, array(
			'timeout'   => 10,
			'headers'   => array( 'Accept' => 'application/json' ),
			'sslverify' => false
		) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$content = wp_remote_retrieve_body( $response );
		
		// Parse Version header
		if ( preg_match( '/Version:\s*(.+)/i', $content, $matches ) ) {
			$version = trim( $matches[1] );
		} else {
			return false;
		}

		$this->github_api_result = (object) array(
			'slug'          => 'rocketslide',
			'plugin'        => $this->plugin_slug,
			'new_version'   => $version,
			'url'           => sprintf( 'https://github.com/%s/%s', $this->github_user, $this->github_repo ),
			'package'       => sprintf( 'https://github.com/%s/%s/archive/refs/heads/main.zip', $this->github_user, $this->github_repo ),
			'tested'        => '6.7',
			'requires'      => '5.5',
			'requires_php'  => '7.4',
			'last_updated'  => date( 'Y-m-d' ),
			'sections'      => array(
				'description' => 'Automatic updates directly from GitHub repository CodeStackLab/rocketslide.',
				'changelog'   => 'See full commit history on GitHub: https://github.com/CodeStackLab/rocketslide/commits/main'
			)
		);

		return $this->github_api_result;
	}

	/**
	 * Check for update and inject into WP transient
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_info = $this->get_remote_info();
		if ( $remote_info && version_compare( $this->version, $remote_info->new_version, '<' ) ) {
			$transient->response[ $this->plugin_slug ] = $remote_info;
		}

		return $transient;
	}

	/**
	 * Plugin Details popup info
	 */
	public function plugin_popup_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || 'rocketslide' !== $args->slug ) {
			return $result;
		}

		$remote_info = $this->get_remote_info();
		if ( $remote_info ) {
			return (object) array(
				'name'          => 'RocketSlide - 9:16 Mobile Landing Page',
				'slug'          => 'rocketslide',
				'version'       => $remote_info->new_version,
				'author'        => '<a href="https://github.com/CodeStackLab">CodeStackLab</a>',
				'homepage'      => $remote_info->url,
				'requires'      => $remote_info->requires,
				'tested'        => $remote_info->tested,
				'download_link' => $remote_info->package,
				'sections'      => $remote_info->sections
			);
		}

		return $result;
	}

	/**
	 * Ensure zip extraction renames folder to 'rocketslide' instead of 'rocketslide-main'
	 */
	public function upgrader_source_selection( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_slug ) {
			$corrected_source = trailingslashit( $remote_source ) . 'rocketslide/';
			if ( $source !== $corrected_source ) {
				global $wp_filesystem;
				$wp_filesystem->move( $source, $corrected_source );
				return $corrected_source;
			}
		}
		return $source;
	}
}
