<?php

class User {

	public $id;
	public $first_name;
	public $last_name;
	public $name;
	public $email;
	public $pass_salt_hash;
	public $is_verified;
	public $is_admin;
	public $is_deleted;
	public $is_active;

	public function __construct($user_id, $first_name, $last_name, $email, $pass_salt_hash, $verified, $admin, $deleted) {
		$this->id = $user_id;
		$this->first_name = $first_name;
		$this->last_name = $last_name;
		$this->name = sprintf('%s %s', $first_name, $last_name);
		$this->email = $email;
		$this->pass_salt_hash = $pass_salt_hash;
		$this->is_verified = $verified;
		$this->is_admin = $admin;
		$this->is_deleted = $deleted;
		$this->is_active = $verified && !$deleted;
	}
}
?>