<?php
/**
 * Movable Type database.
 *
 * @package movabletype-importer-fixers
 */

/**
 * Movable database class.
 *
 * @package movabletype-importer-fixers
 */
class MT_Database {

	/**
	 * Instance for wpdb.
	 *
	 * @var $db
	 */
	public $db;

	/**
	 * Database name.
	 *
	 * @var $db_name
	 */
	private $db_name = MT_DB_NAME;

	/**
	 * Database user.
	 * Here readonly user can work as well.
	 *
	 * @var $db_user
	 */
	private $db_user = DB_USER;

	/**
	 * Database password.
	 *
	 * @var $db_password
	 */
	private $db_password = DB_PASSWORD;

	/**
	 * Database host.
	 *
	 * @var $db_host
	 */
	private $db_host = DB_HOST;

	/**
	 * Class constructor.
	 * Initializes the class with necessary member methods.
	 */
	public function __construct() {

		error_reporting( E_ALL & ~E_WARNING );
		if ( defined( MT_DB_HOST ) && ! empty( MT_DB_HOST ) ) {
			$this->db_host = MT_DB_HOST;
		}

		if ( defined( MT_DB_USER ) && ! empty( MT_DB_USER ) ) {
			$this->db_user = MT_DB_USER;
		}

		if ( defined( MT_DB_PASSWORD ) && ! empty( MT_DB_PASSWORD ) ) {
			$this->db_password = MT_DB_PASSWORD;
		}
		error_reporting( E_ALL );

		// Intentionally not adding not empty check for db_password. Sometimes it can be blank.
		if ( ! empty( $this->db_name ) && ! empty( $this->db_user ) && ! empty( $this->db_host ) ) {

			$this->db = new \wpdb( $this->db_user, $this->db_password, $this->db_name, $this->db_host );
		}
	}

	/**
	 * To get instance of wpdb.
	 *
	 * @return bool|object
	 */
	public function get_db_instance() {
		return ( ! empty( $this->db ) && $this->db->db_connect() ) ? $this->db : false;
	}
}

