<?php

class User extends DB_Model_Base{
    public $id;
    public $surname;

    public function __construct() {
		$this->table_name  = 'baby_users';
		$this->primary_key = 'id';
	}

    public static function get_columns(){
        return array(
            new DB_Column("id", __('id', 'BabysittereiApplicationAdmin'), "bigint", "(19) NOT NULL AUTO_INCREMENT"),
            new DB_Column("surname", __('surname', 'BabysittereiApplicationAdmin'), "nvarchar", "(100)"),
        );
    }
}
