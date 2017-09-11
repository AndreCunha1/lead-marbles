<?php

class ETC_User extends BasicClass {

	/* Properties */

	protected $ID;
	protected $name;
	protected $email;
	protected $avatar;
	protected $settings;
	protected $languageCode;

	protected $selectedType;	// "folder" or "text"
	protected $selectedFolderID;
	protected $selectedTextsIDs;


	/* Constructor & Destructor */

	public function __construct ( $user_id = 0 ) {
		parent::__construct( get_class( $this ) );
		array_unshift( $this->properties_to_set, 'name', 'email', 'avatar', 'settings' );

		$this->ID					= intval( $user_id, 10 );
		$this->name					= '';
		$this->email				= '';
		$this->avatar				= '';
		$this->settings				= '';
		$this->languageCode			= '';

		$this->selectedType			= 'folder';
		$this->selectedFolderID		= 1;
		$this->selectedTextsIDs		= array();

		if ( empty( $this->ID ) ) {
			$this->registerLog( 'error', __METHOD__.'() invalid ID' );
		} else {
			$this->fetchUserData();
		}
	}


	/* Protected Methods */

	protected function fetchUserData () {
		global $pdo_handler;
		$user_data = $pdo_handler->query( 'SELECT *
											FROM `etc_users`
											WHERE `user_id` = :ID',
											array( ':ID' => $this->ID ) );
		//$this->ID					= intval( $user_data[0]['user_id'], 10 );
		$this->name					= $user_data[0]['name'];
		$this->email				= $user_data[0]['email'];
		$this->avatar				= empty( $user_data[0]['photo'] ) ? 'default.png' : $user_data[0]['photo'];
		$this->settings				= $user_data[0]['configuracoes'];
		$this->languageCode			= $user_data[0]['language'];
	}

	protected function setSelectedType ( $selectionType ) {
		$this->selectedType = strtolower( trim( $selectionType ) );
	}


	/* Public Methods */

	public function setLanguageByCode ( $languageCode ) {
		global $pdo_handler;
		$languageCode = trim( $languageCode );
		$this->languageCode = $languageCode;
		$pdo_handler->query( 'UPDATE `etc_users`
								SET `language` = :languageCode
								WHERE `user_id` = :ID',
								array( ':languageCode' => $languageCode,
										':ID' => $this->ID ) );
	}

	public function setSelectedFolderID ( $folder_id ) {
		if ( empty( $folder_id ) ) {
			$this->selectedFolderID = 1;
		} else {
			$this->selectedFolderID = intval( $folder_id, 10 );
		}
		$this->selectedTextsIDs = array();
		$this->setSelectedType( 'folder' );
	}

	public function toggleSelectedTextID ( $text_id ) {
		if ( !empty( $text_id ) ) {
			$text_id = intval( $text_id, 10 );
			if ( array_key_exists( $text_id, $this->selectedTextsIDs ) ) {
				unset( $this->selectedTextsIDs[$text_id] );
			} else {
				$this->selectedTextsIDs[$text_id] = TRUE;
			}

			if ( empty( $this->selectedTextsIDs ) ) {
				$this->setSelectedType( 'folder' );
			} else {
				$this->setSelectedType( 'text' );
			}
		}
	}

}

?>
