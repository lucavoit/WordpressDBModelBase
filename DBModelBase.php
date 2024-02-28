<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 *  This handy class originated from Pippin's Easy Digital Downloads.
 * https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/class-edd-db.php
 *
 * Sub-classes should define $table_name, $version, and $primary_key in __construct() method.
 * 
 * @package     EDD
 * @subpackage  Classes/EDD DB
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/
abstract class DB_Model_Base {

	/**
	 * The name of our database table
	 */
	public $table_name;

	/**
	 * The name of the primary column
	 */
	public $primary_key;

	public function __construct() {}

	/**
	 * Whitelist of columns
	 */
	public function get_column_ids() {
		return array_map(function ($column) {
			return $column->id;
		}, self::get_columns());
	}

	/**
	 * Default column values
	 */
	public function get_column_defaults() {
		$defaults = [];

        foreach (self::get_columns() as $column) {
            $defaults[$column->id] = $column->default;
        }

        return $defaults;
	}

	/**
	 * Retrieve a row by the primary key
	 */
	public function get( $row_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
		$this->fromDB($row);
		return $row;
	}

	/**
	 * Retrieve a row by a specific column / value
	 */
	public function get_by( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id ) );
		$this->fromDB($row);
		return $row;
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 */
	public function get_column( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );
		return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;
		$column_where = esc_sql( $column_where );
		$column       = esc_sql( $column );
		return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value ) );
	}

	public function fromDB(array $dbValues) {
        foreach ($dbValues as $columnName => $value) {
            if (property_exists($this, $columnName)) {
                $this->$columnName = $value;
            }
        }
    }

    public function toDB() {
        $dbValues = [];
		$whitelist = $this->get_column_ids();
        foreach ($this as $property => $value) {
			if(in_array($property, $whitelist)){
				$dbValues[$property] = $value;
			}
        }
        return $dbValues;
    }

	/**
	 * Insert a new row
	 */
	public function insert(  ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $this->toDB(), $this->get_column_defaults() );

		// Initialise column format array
		$column_formats = $this->get_column_ids();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );
		$wpdb_insert_id = $wpdb->insert_id;

		return $wpdb_insert_id;
	}

	/**
	 * Update a row
	 */
	public function update( $row_id, $data = null, $where = '' ) {

		global $wpdb;

		$data = is_null($data) ? $this->toDB() : $data;
		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if( empty( $row_id ) ) {
			return false;
		}

		if( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_column_ids();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 */
	public function delete( $row_id = 0 ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if( empty( $row_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the given table exists
	 */
	public function table_exists( $table ) {
		global $wpdb;
		$table = sanitize_text_field( $table );

		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;
	}

	/**
	 * Check if the table was ever installed
	 */
	public function installed() {
		return $this->table_exists( $this->table_name );
	}

	/**
	 * Install the table
	 */
	public function install(){
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_name;
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (";
		foreach ($this::get_columns() as $column) {
            $sql = $sql . " $column->id $column->type$column->typeAddendum,";
		}
		$sql = $sql . " PRIMARY KEY  ($this->primary_key)) $charset_collate;";
		dbDelta( $sql );
	}
	
	abstract public static function get_columns();
}

class DB_Column{
	public $id;
	public $type;
	public $typeAddendum;
	public $label;
	public $default;

	public function __construct($id, $label, $type, $typeAddendum = "", $default = null) {
        $this->id = $id;
        $this->type = $type;
        $this->typeAddendum = $typeAddendum;
        $this->label = $label;
        $this->default = $default;
    }
}
