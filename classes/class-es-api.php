<?php

class ES_API {

	public function __construct() { }

	/**
	 * Return singleton instance of class
	 *
	 * @return ES_API
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance  ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Index a post under a given site index or the global index ($site_id = 0)
	 *
	 * @param array $post
	 * @param int $site_id
	 * @return array|bool|mixed
	 */
	public function index_post( $post, $site_id = null ) {

		$index_url = es_get_index_url( $site_id );

		$url = $index_url . '/post/';

		if ( ! empty( $post['site_id'] ) && $post['site_id'] > 1 ) {
			$url .= $post['site_id'] . 'ms' . $post['post_id'];
		} else {
			$url .= $post['post_id'];
		}

		$request = wp_remote_request( $url, array( 'body' => json_encode( $post ), 'method' => 'PUT' ) );

		if ( ! is_wp_error( $request ) ) {
			$response_body = wp_remote_retrieve_body( $request );

			return json_decode( $response_body );
		}

		return false;
	}

	/**
	 * Search for posts under a specific site index or the global index ($site_id = 0).
	 *
	 * @param array $args
	 * @param int $site_id
	 * @return array
	 */
	public function search( $args, $site_id = null ) {
		$index_url = es_get_index_url( $site_id );

		$url = $index_url . '/post/_search';

		$request = wp_remote_request( $url, array( 'body' => json_encode( $args ), 'method' => 'POST' ) );

		if ( ! is_wp_error( $request ) ) {
			$response_body = wp_remote_retrieve_body( $request );

			$response = json_decode( $response_body, true );

			if ( $this->is_empty_search( $response ) ) {
				return array();
			}

			$hits = $response['hits']['hits'];

			return wp_list_pluck( $hits, '_source' );
		}

		return array();
	}

	/**
	 * Check if a response array contains results or not
	 *
	 * @param array $response
	 * @return bool
	 */
	public function is_empty_search( $response ) {

		if ( ! is_array( $response ) ) {
			return true;
		}

		if ( isset( $response['error'] ) ) {
			return true;
		}

		if ( empty( $response['hits'] ) ) {
			return true;
		}

		return false;
	}
}

global $es_api;
$es_api = ES_API::factory();

/**
 * Accessor functions for methods in above class. See doc blocks above for function details.
 */

function es_index_post( $post, $site_id = null ) {
	global $es_api;

	return $es_api->index_post( $post, $site_id );
}

function es_search( $args, $site_id = null ) {
	global $es_api;

	return $es_api->search( $args, $site_id );
}