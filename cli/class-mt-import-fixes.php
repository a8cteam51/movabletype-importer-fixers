<?php

WP_CLI::add_command( 'mt-wp-cli', 'MT_Import_Fixes' );

class MT_Import_Fixes extends MT_Migration_Base {

	/**
	 * To migrate MT custom fields.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Whether or not to do dry run.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 *
	 * [--blog-id]
	 * : Blog id if MT is having multiple blog.
	 * ---
	 * default: 1
	 * options:
	 *   - 1
	 *   - 2
	 *   - 3
	 *
	 * [--post-id-offset]
	 * : Post-ID offset for delta migration.
	 * ---
	 * default: false
	 * options:
	 *   - 10
	 *   - 10000
	 *
	 * [--field-names]
	 * : Field Name from Movable type. Comma (,) separated names.
	 * ---
	 * default: ''
	 * options:
	 *   - link
	 *   - link,podcast,mp3,sitename,leadimage,leadimagecaption
	 *
	 * [--field-type]
	 * : Field type.
	 * ---
	 * default: postmeta
	 * options:
	 *   - postmeta
	 *   - ACF
	 *
	 * ## EXAMPLES
	 *
	 *   wp mt-wp-cli migrate-meta-values
	 *
	 * @subcommand migrate-meta-values
	 *
	 * @param array $args Store all the positional arguments.
	 * @param array $assoc_args Store all the associative arguments.
	 *
	 * @throws \Exception If any errors.
	 */
	public function mt_migrate_meta_values( $args, $assoc_args ) {

		// Starting time of the script.
		$start_time = time();

		if ( empty( $assoc_args['field-names'] ) ) {
			$this->error( sprintf( 'Please pass --field-names option with comma (,) separated filed names.' ) );
		}

		// To enable WP_IMPORTING.
		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		if ( ! empty( $assoc_args['dry-run'] ) && 'false' === $assoc_args['dry-run'] ) {

			$this->dry_run = false;
		}

		if ( ! empty( $assoc_args['blog-id'] ) && intval( $assoc_args['blog-id'] ) && 0 !== intval( $assoc_args['blog-id'] ) ) {

			$this->blog_id = intval( $assoc_args['blog-id'] );
		}

		$field_names = explode( ',', $assoc_args['field-names'] );

		$field_type = 'postmeta';

		if ( ! empty( $assoc_args['field-type'] ) && in_array( $assoc_args['field-type'], array( 'ACF' ), true ) ) {

			$field_type = $assoc_args['field-type'];
			$this->write_log( 'Meta values will be migrated to ACF fields.' );
		} else {
			$this->write_log( 'Meta values will be migrated to WP postmeta.' );
		}

		if ( ! empty( $assoc_args['post-id-offset'] ) && 'false' !== $assoc_args['post-id-offset'] && intval( $assoc_args['post-id-offset'] ) ) {

			$this->post_id_delta_offset = intval( $assoc_args['post-id-offset'] );
		}

		$this->init_mt_db();

		$query = sprintf( 'SELECT entry_id, entry_title, REPLACE( entry_basename, "_", "-" ) as entry_basename FROM `mt_entry` WHERE entry_blog_id=%d AND entry_class=\'entry\'', $this->blog_id );

		$posts         = $this->mt_db->get_results( $query, ARRAY_A );
		$total_found   = count( $posts );
		$success_count = 0;

		foreach ( $posts as $key => $post ) {
			$wp_post = get_page_by_title( $post['entry_title'], ARRAY_A, 'post' );
			if ( empty( $wp_post ) ) {
				$wp_post = get_page_by_path( $post['entry_basename'], ARRAY_A, 'post' );
			}

			if ( ! empty( $wp_post['ID'] ) ) {
				if ( false !== $this->post_id_delta_offset && $this->post_id_delta_offset >= (int) $wp_post['ID'] ) {
					$total_found--;
					continue;
				}
				$meta_query = sprintf( 'SELECT fdvalue_key ,fdvalue_value from mt_fdvalue where fdvalue_blog_id=%d AND fdvalue_object_id=%d', $this->blog_id, intval( $post['entry_id'] ) );
				$meta_info  = $this->mt_db->get_results( $meta_query, ARRAY_A );
				foreach ( $meta_info as $meta_row ) {
					if ( ! empty( $meta_row['fdvalue_key'] ) && in_array( $meta_row['fdvalue_key'], $field_type, true ) ) {

						if ( ! $this->dry_run && 'ACF' === $field_type ) {
							update_field( $meta_row['fdvalue_key'], $meta_row['fdvalue_value'], $wp_post['ID'] );
						} elseif ( ! $this->dry_run ) {
							update_post_meta( $wp_post['ID'], $meta_row['fdvalue_key'], $meta_row['fdvalue_value'] );
						}
						$success_count++;
					}
				}
			}
		}

		if ( $this->dry_run ) {

			$this->success( sprintf( 'Total %d posts will be processed.', $total_found ) );

		} else {

			$this->success( sprintf( 'Total %d posts have been processed.', $success_count ) );
		}

		WP_CLI::line( '' );
		WP_CLI::success( sprintf( 'Total time taken by this script: %s', human_time_diff( $start_time, time() ) ) . PHP_EOL . PHP_EOL );
	}

	/**
	 * To migrate tags.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Whether or not to do dry run.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 *
	 * [--post-id-offset]
	 * : Post-ID offset for delta migration.
	 * ---
	 * default: false
	 * options:
	 *   - 10
	 *   - 10000
	 *
	 * [--blog-id]
	 * : Blog id if MT is having multiple blog.
	 * ---
	 * default: 1
	 * options:
	 *   - 1
	 *   - 2
	 *   - 3
	 *
	 * ## EXAMPLES
	 *
	 *   wp mt-wp-cli migrate-tags
	 *
	 * @subcommand migrate-tags
	 *
	 * @param array $args Store all the positional arguments.
	 * @param array $assoc_args Store all the associative arguments.
	 *
	 * @throws \Exception If any errors.
	 */
	public function mt_migrate_tags( $args, $assoc_args ) {

		// Starting time of the script.
		$start_time = time();

		// To enable WP_IMPORTING.
		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		if ( ! empty( $assoc_args['dry-run'] ) && 'false' === $assoc_args['dry-run'] ) {

			$this->dry_run = false;
		}

		if ( ! empty( $assoc_args['blog-id'] ) && intval( $assoc_args['blog-id'] ) && 0 !== intval( $assoc_args['blog-id'] ) ) {

			$this->blog_id = intval( $assoc_args['blog-id'] );
		}

		if ( ! empty( $assoc_args['post-id-offset'] ) && 'false' !== $assoc_args['post-id-offset'] && intval( $assoc_args['post-id-offset'] ) ) {

			$this->post_id_delta_offset = intval( $assoc_args['post-id-offset'] );
		}

		$this->init_mt_db();

		$query = sprintf(
			'SELECT mtot.objecttag_object_id, mtot.objecttag_tag_id, t1.tag_name, t2.entry_title, t2.entry_basename FROM `mt_objecttag`as mtot
				LEFT JOIN ( SELECT tag_id, tag_name from mt_tag ) as t1 on ( t1.tag_id=mtot.objecttag_tag_id )
				LEFT JOIN ( SELECT entry_id, entry_title, replace(entry_basename, "_", "-") as entry_basename from mt_entry ) as t2 on ( t2.entry_id=mtot.objecttag_object_id )
				WHERE objecttag_blog_id=%d AND objecttag_object_datasource=\'entry\'',
			$this->blog_id
		);

		$posts         = $this->mt_db->get_results( $query, ARRAY_A );
		$total_found   = count( $posts );
		$success_count = 0;

		foreach ( $posts as $key => $post ) {
			$wp_post = get_page_by_title( $post['entry_title'], ARRAY_A, 'post' );
			if ( empty( $wp_post ) ) {
				$wp_post = get_page_by_path( $post['entry_basename'], ARRAY_A, 'post' );
			}

			if ( ! empty( $wp_post['ID'] ) && ! empty( $post['tag_name'] ) ) {

				if ( false !== $this->post_id_delta_offset && $this->post_id_delta_offset >= (int) $wp_post['ID'] ) {
					$total_found--;
					continue;
				}

				if ( ! $this->dry_run ) {
					wp_set_post_tags( $wp_post['ID'], $post['tag_name'], true );
				}
				$success_count++;
			}
		}

		if ( $this->dry_run ) {

			$this->success( sprintf( 'Total %d posts will be processed.', $total_found ) );

		} else {

			$this->success( sprintf( 'Total %d posts have been processed.', $success_count ) );
		}

		WP_CLI::line( '' );
		WP_CLI::success( sprintf( 'Total time taken by this script: %s', human_time_diff( $start_time, time() ) ) . PHP_EOL . PHP_EOL );
	}

	/**
	 * To overwrite post-content.
	 * Purpose: This is required to migrate the original MT markdown to post content along with extra extended content of post.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Whether or not to do dry run.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 *
	 * [--post-status]
	 * : Post status.
	 * ---
	 * default: publish
	 * options:
	 *   - publish
	 *   - draft
	 *
	 * [--post-id-offset]
	 * : Post-ID offset for delta migration.
	 * ---
	 * default: false
	 * options:
	 *   - 10
	 *   - 10000
	 *
	 * ## EXAMPLES
	 *
	 *   wp mt-wp-cli overwrite-content
	 *
	 * @subcommand overwrite-content
	 *
	 * @param array $args Store all the positional arguments.
	 * @param array $assoc_args Store all the associative arguments.
	 *
	 * @throws \Exception If any errors.
	 */
	public function mt_overwrite_post_content( $args, $assoc_args ) {

		// Starting time of the script.
		$start_time = time();

		// To enable WP_IMPORTING.
		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		if ( ! empty( $assoc_args['dry-run'] ) && 'false' === $assoc_args['dry-run'] ) {

			$this->dry_run = false;
		}

		if ( ! empty( $assoc_args['post-status'] ) && 'draft' === $assoc_args['dry-run'] ) {

			$this->post_status = 'draft';
		}

		if ( ! empty( $assoc_args['post-id-offset'] ) && 'false' !== $assoc_args['post-id-offset'] && intval( $assoc_args['post-id-offset'] ) ) {

			$this->post_id_delta_offset = intval( $assoc_args['post-id-offset'] );
		}

		$this->init_mt_db();

		global $wpdb;

		if ( false === $this->post_id_delta_offset ) {
			$query = sprintf(
				'SELECT p.ID, p.post_content, t1.entry_text, t1.entry_text_more, t1.entry_basename
			FROM `%s`.%sposts as p
			LEFT JOIN ( SELECT entry_text, entry_text_more, entry_title, REPLACE( entry_basename, "_", "-" ) as entry_basename  from `%s`.mt_entry ) as t1 on ( t1.entry_basename=p.post_name OR t1.entry_title=p.post_title ) WHERE p.post_type="post" AND p.post_status="%s"',
				DB_NAME,
				$wpdb->prefix,
				MT_DB_NAME,
				$this->post_status
			);
		} else {
			$query = sprintf(
				'SELECT p.ID, p.post_content, t1.entry_text, t1.entry_text_more, t1.entry_basename
			FROM `%s`.%sposts as p
			LEFT JOIN ( SELECT entry_text, entry_text_more, entry_title, REPLACE( entry_basename, "_", "-" ) as entry_basename  from `%s`.mt_entry ) as t1 on ( t1.entry_basename=p.post_name OR t1.entry_title=p.post_title ) WHERE p.post_type="post" AND p.post_status="%s" AND p.ID>%d',
				DB_NAME,
				$wpdb->prefix,
				MT_DB_NAME,
				$this->post_status,
				$this->post_id_delta_offset
			);
		}

		$posts         = $this->mt_db->get_results( $query, ARRAY_A );
		$total_found   = count( $posts );
		$success_count = 0;

		foreach ( $posts as $key => $post ) {

			if ( ! empty( $post['entry_text'] ) && ! empty( $post['ID'] ) ) {

				if ( ! $this->dry_run ) {

					if ( ! empty( $post['entry_text_more'] ) ) {
						$post['entry_text'] .= "\n\n";
						$post['entry_text'] .= $post['entry_text_more'];
					}

					$status = wp_update_post(
						array(
							'ID'           => $post['ID'],
							'post_content' => $post['entry_text'],
						),
						true
					);

					if ( ! is_wp_error( $status ) ) {
						$success_count++;
					}
				} else {
					$success_count++;
				}
			}
		}

		if ( $this->dry_run ) {

			$this->success( sprintf( 'Total %d posts will be processed.', $total_found ) );

		} else {

			$this->success( sprintf( 'Total %d posts have been processed.', $success_count ) );
		}

		WP_CLI::line( '' );
		WP_CLI::success( sprintf( 'Total time taken by this script: %s', human_time_diff( $start_time, time() ) ) . PHP_EOL . PHP_EOL );
	}

	/**
	 * To sync the MT markdown with Jetpack.
	 * Purpose: When Jetpack is active and post with MT markdown is being saved without any change, Jetpack will help render the MT mrakdown.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Whether or not to do dry run.
	 * ---
	 * default: false
	 * options:
	 *   - true
	 *   - false
	 *
	 * [--limit]
	 * : Limit for pagination.
	 * ---
	 * default: 100
	 * options:
	 *   - 1000
	 *   - 10000
	 *
	 * [--page]
	 * : Page count.
	 * ---
	 * default: false
	 * options:
	 *   - 1
	 *   - 2
	 *
	 * [--post-type]
	 * : Post type.
	 * ---
	 * default: post
	 * options:
	 *   - post
	 *   - page
	 *
	 * ## EXAMPLES
	 *
	 *   wp mt-wp-cli sync-jetpack-markdown
	 *
	 * @subcommand sync-jetpack-markdown
	 *
	 * @param array $args Store all the positional arguments.
	 * @param array $assoc_args Store all the associative arguments.
	 *
	 * @throws \Exception If any errors.
	 */
	public function markdown_sync( $args, $assoc_args ) {

		// Starting time of the script.
		$start_time = time();

		// To enable WP_IMPORTING.
		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		if ( ! empty( $assoc_args['dry-run'] ) && 'false' === $assoc_args['dry-run'] ) {

			$this->dry_run = false;
		}

		$post_type = 'post';
		if ( ! empty( $assoc_args['post-type'] ) ) {

			$post_type = $assoc_args['post-type'];
		}

		$limit = 100;
		if ( ! empty( $assoc_args['limit'] ) && intval( $assoc_args['limit'] ) ) {

			$limit = intval( $assoc_args['limit'] );
		}

		$page = false;
		if ( ! empty( $assoc_args['page'] ) && intval( $assoc_args['page'] ) ) {

			$page = $assoc_args['page'];
		}

		$args = array(
			'numberposts' => $limit,
			'orderby'     => 'ID',
			'order'       => 'DESC',
			'fields'      => 'ids',
			'post_type'   => $post_type,
		);

		if ( false !== $page ) {
			$args['page'] = $page;
		}

		$posts         = get_posts( $args ); // @codingStandardsIgnoreLine: No need to maintain the caching here, so get_posts is okay to use.
		$total_found   = count( $posts );
		$success_count = 0;

		if ( ! $this->dry_run ) {
			foreach ( $posts as $post ) {
				$wp_post = get_post( $post, ARRAY_A );

				$status = wp_update_post(
					array(
						'ID'           => $wp_post['ID'],
						'post_content' => $wp_post['post_content'],
					),
					true
				);

				if ( ! is_wp_error( $status ) ) {
					$success_count++;
				}
			}
		} else {
			$success_count = $total_found;
		}

		if ( $this->dry_run ) {

			$this->success( sprintf( 'Total %d posts will be processed.', $total_found ) );

		} else {

			$this->success( sprintf( 'Total %d posts have been processed.', $success_count ) );
		}

		WP_CLI::line( '' );
		WP_CLI::success( sprintf( 'Total time taken by this script: %s', human_time_diff( $start_time, time() ) ) . PHP_EOL . PHP_EOL );
	}
}
