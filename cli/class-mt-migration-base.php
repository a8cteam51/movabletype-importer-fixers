<?php
/**
 * To migrates the Divi shortcodes to gutenberg blocks.
 *
 * @since   1.0.0
 * @package movabletype-importer-fixers
 */

WP_CLI::add_command( 'mt-wp-cli', 'MT_Migration_Base' );

require_once 'class-mt-database.php';

class MT_Migration_Base extends WP_CLI_Command {

	protected $dry_run   = true;
	protected $post_type = 'post';
	protected $mt_db     = false;
	protected $blog_id   = 1;

	/**
	 * To init the MT db.
	 *
	 * @param bool $return
	 *
	 * @return bool|object|void
	 */
	protected function init_mt_db( $return = false ) {

		$db          = new MT_Database();
		$this->mt_db = $db->get_db_instance();

		if ( $return ) {
			return $this->mt_db;
		}
	}

	/**
	 * To test database connection for Movable Type.
	 *
	 * ## EXAMPLES
	 *
	 *   wp mt-wp-cli test-db-connection
	 *
	 * @subcommand test-db-connection
	 *
	 * @param array $args       Store all the positional arguments.
	 * @param array $assoc_args Store all the associative arguments.
	 */
	public function test_mt_db_connection( $args = array(), $assoc_args = array() ) {

		$this->init_mt_db();

		if ( false === $this->mt_db ) {
			$this->error( 'Database connection failed!' );
		} else {
			$this->success( 'Database connected!' );
		}
	}

	/**
	 * Hook callback to alter the post modification time.
	 * This needs to be added to update the post_modified time while inserting or updating the post.
	 *
	 * @param array $data     Data.
	 * @param array $post_arr Post array.
	 *
	 * @return mixed
	 */
	private function alter_post_modification_time( $data, $post_arr ) {

		if ( ! empty( $post_arr['post_modified'] ) && ! empty( $post_arr['post_modified_gmt'] ) ) {
			$data['post_modified']     = $post_arr['post_modified'];
			$data['post_modified_gmt'] = $post_arr['post_modified_gmt'];
		}

		return $data;
	}

	/**
	 * Create log files.
	 *
	 * @param string $file_name File name.
	 * @param array  $logs      Log array.
	 */
	private function create_log_file( $file_name, $logs ) {

		$uploads     = wp_get_upload_dir();
		$source_file = $uploads['basedir'] . '/mt-migration-logs/';

		if ( ! file_exists( $source_file ) ) {
			mkdir( $source_file, 0777, true );
		}

		$file = fopen( $source_file . $file_name, 'w' ); // @codingStandardsIgnoreLine

		foreach ( $logs as $row ) {
			fputcsv( $file, $row );
		}

		$csv_generated = fclose( $file ); // @codingStandardsIgnoreLine

		if ( $csv_generated ) {
			$this->write_log( sprintf( 'Log created successfully - %s', $file_name ) );
		} else {
			$this->warning( sprintf( 'Failed to write the logs - %s', $file_name ) );
		}
	}

	/**
	 * Method to add a log entry and to output message on screen
	 *
	 * @param string $msg             Message to add to log and to outout on screen.
	 * @param int    $msg_type        Message type - 0 for normal line, -1 for error, 1 for success, 2 for warning.
	 * @param bool   $suppress_stdout If set to TRUE then message would not be shown on screen.
	 * @return void
	 */
	protected function write_log( $msg, $msg_type = 0, $suppress_stdout = false ) {

		// backward compatibility.
		if ( true === $msg_type ) {
			// its an error
			$msg_type = -1;
		} elseif ( true === $msg_type ) {
			// normal message
			$msg_type = 0;
		}

		$msg_type = intval( $msg_type );

		$msg_prefix = '';

		// Message prefix for use in log file
		switch ( $msg_type ) {

			case -1:
				$msg_prefix = 'Error: ';
				break;

			case 1:
				$msg_prefix = 'Success: ';
				break;

			case 2:
				$msg_prefix = 'Warning: ';
				break;

		}

		// If we don't want output shown on screen then
		// bail out.
		if ( true === $suppress_stdout ) {
			return;
		}

		switch ( $msg_type ) {

			case -1:
				WP_CLI::error( $msg );
				break;

			case 1:
				WP_CLI::success( $msg );
				break;

			case 2:
				WP_CLI::warning( $msg );
				break;

			case 0:
			default:
				WP_CLI::line( $msg );
				break;

		}

	}

	/**
	 * Method to log an error message and stop the script from running further
	 *
	 * @param string $msg Message to add to log and to outout on screen
	 * @return void
	 */
	protected function error( $msg ) {
		$this->write_log( $msg, -1 );
	}

	/**
	 * Method to log a success message
	 *
	 * @param string $msg Message to add to log and to outout on screen
	 * @return void
	 */
	protected function success( $msg ) {
		$this->write_log( $msg, 1 );
	}

	/**
	 * Method to log a warning message
	 *
	 * @param string $msg Message to add to log and to outout on screen
	 * @return void
	 */
	protected function warning( $msg ) {
		$this->write_log( $msg, 2 );
	}
}
