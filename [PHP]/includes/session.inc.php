<?php

class Session extends BasicClass {

	/* Properties */

	// páginas que não requerem usuário logado
	protected static $allowedPages = array( 'index.php', 'view_text.php', 'token.php', 'login/login.php', 'login/sign_up.php', 'login/forgot_pass.php', 'account/email_confirm.php' );

	protected $ID;					// ID da sessão
	protected $name;				// nome da sessão (também nome da key utilizada para armazenar a classe no array $_SESSION)
	protected $IP;					// IP do cliente
	protected $loggedUsers;			// array de usuários logados
	protected $currentUserID;		// ID do usuário em uso
	protected $currentLanguageCode;	// código do idioma em uso


	/* Constructor & Destructor */

	public function __construct ( $name = 'ETC' ) {
		parent::__construct( get_class( $this ) );
		//array_unshift( $this->properties_to_set, 'ID', 'name' );

		$name = trim( $name );
		if ( empty( $name ) ) {
			$name = 'SESSION_UNNAMED';
		}

		self::resume( $name );

		$this->ID					= session_id();
		$this->loggedUsers			= array();
		$this->name					= $name;
		$this->IP					= $_SERVER['REMOTE_ADDR'];
		$this->currentUserID		= 0;
		$this->currentLanguageCode	= '';
	}

	public function __destruct () {
		/*
		¯\_(ツ)_/¯
		*/
	}


	/* Public Static Methods */

	public static function checkActive () {
		if ( trim( session_id() ) == FALSE ) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	public static function destroy ( $name = 'ETC' ) {
		$name = trim( $name );
		if ( empty( $name ) ) {
			$name = 'SESSION_UNNAMED';
		}

		self::resume( $name );
		$_SESSION = array(); // unset all of the session variables, equivalent to session_unset();
		session_unset();
		self::delCookie();
		session_destroy();
	}

	public static function resume ( $name = 'ETC' ) {
		$name = trim( $name );
		if ( empty( $name ) ) {
			$name = 'SESSION_UNNAMED';
		}

		if ( self::checkActive() !== TRUE ) {
			session_name( $name );
			session_start();
		}

		if ( self::checkActive() === FALSE ) {
			echo '<br /><br />ERROR: UNAVAILABLE SESSION<br /><br />';
			exit;
		}

		self::setCookie();
	}

	public static function reset ( $name = 'ETC' ) {
		$name = trim( $name );
		if ( empty( $name ) ) {
			$name = 'SESSION_UNNAMED';
		}

		self::destroy();
		return $_SESSION[$name] = new self();
	}

	public static function getSession ( $name = 'ETC' ) {
		$name = trim( $name );
		if ( empty( $name ) ) {
			$name = 'SESSION_UNNAMED';
		}

		self::resume( $name );

		if ( empty( $_SESSION[$name] ) ) { // sessão ativa não possui os dados esperados
			return self::reset( $name );
		} else {
			if ( $_SESSION[$name]->ID !== session_id() ) { // sessão ativa está com ID inválido
				return self::reset( $name );
			} else { // sessão ativa está com ID válido
				//session_regenerate_id( TRUE );
				//$_SESSION[$name]->ID = session_id();
				//self::setCookie();
				return $_SESSION[$name];
			}
		}
	}


	/* Protected Static Methods */

	protected static function setCookie () {
		global $session_timeout;
		if ( ini_get( 'session.use_cookies' ) ) {
			$session_cookie_parameters = session_get_cookie_params();
			setcookie( session_name(),
						session_id(),
						time() + $session_timeout,
						$session_cookie_parameters['path'],
						$session_cookie_parameters['domain'],
						$session_cookie_parameters['secure'],
						$session_cookie_parameters['httponly']
			);
			$_COOKIE[session_name()] = session_id();
		}
	}

	protected static function delCookie () {
		if ( ini_get( 'session.use_cookies' ) ) {
			$session_cookie_parameters = session_get_cookie_params();
			setcookie( session_name(),
						'',
						time() - 86400, // one day (in seconds)
						$session_cookie_parameters['path'],
						$session_cookie_parameters['domain'],
						$session_cookie_parameters['secure'],
						$session_cookie_parameters['httponly']
			);
			unset( $_COOKIE[session_name()] );
		}
	}

	protected static function redirect ( $destination, $statusCode = 303 ) {
		$destinationURL = '';
		switch ( strtolower( $destination ) ) {
			case 'logged':
				$destinationURL = ETC_LOGGED_PAGE;
			break;
			case 'logout':
				$destinationURL = ETC_BASE_URL;
			break;
			case 'expired':
				$destinationURL = ETC_BASE_URL.'?warning=war_session_expired';
			break;
			default:
				return FALSE;
			break;
		}
		//header( 'Location: '.$destinationURL, TRUE, $statusCode ); // PHP redirect (insufficient when page is inside an iframe)
		?>
		<script type="text/javascript">
			if ( opener ) {
				opener.location.href = '<?php echo $destinationURL; ?>';
				window.close();
			}
			for ( var root = window; root.parent != root; root = root.parent );
			root.location.href = '<?php echo $destinationURL; ?>';
		</script>
		<?php
		exit;
	}

	protected static function getCurrentScriptName () {
		// TODO
		//return substr( trim( $_SERVER['SCRIPT_NAME'], '\\\/ ' ), mb_strlen( ETC_FOLDER_NAME.DIRECTORY_SEPARATOR ) );
	}

	protected static function allowedPage () {
		return in_array( self::getCurrentScriptName(), self::$allowedPages );
	}

	protected static function ajaxPage () {
		// TODO TO DO TO-DO: change ajax pages to have a isset( $_GET['ajax'] ) in order to detect ajaxes
		return strpos( basename( $_SERVER['SCRIPT_NAME'] ), 'ajax_' ) === 0 ? TRUE : FALSE;
	}


	/* Protected Methods */

	protected function checkPermission () {
		if ( self::ajaxPage() === TRUE ) { // página ajax
			$current_user = $this->getCurrentUser();
			if ( empty( $current_user ) ) { // abortar script em páginas ajax sem usuário
				//global $translator; echo $translator->getTranslation( 'war_session_expired' ).'<br />'; // não vai funcionar pois o $translator só é instanciado depois
				exit;
			}
		} else { // não é uma página ajax
			if ( $this->currentUserID === 0 ) { // sessão atual não possui usuário logado
				if ( self::allowedPage() === FALSE ) { // página requer usuário logado
					self::redirect( 'expired' );
				}
			} else { // sessão atual possui usuário logado
				if ( self::getCurrentScriptName() === 'index.php' ) { // está na página inicial
					self::redirect( 'logged' );
				}
			}
		}
	}


	/* Public Methods */

	public function check () {
		self::resume( $this->name );
		if ( $this->ID !== session_id() ) { // sessão está inválida ou expirada
			self::reset( $this->name );
		}
		$this->checkPermission();
	}

	public function loginUser ( $user ) {
		self::resume( $this->name );
		$this->loggedUsers[$user->getProperty( 'ID' )] = $user;
		$this->currentUserID = $user->getProperty( 'ID' );
		$this->setCurrentLanguageByCode( $user->getProperty( 'languageCode' ) );
	}

	public function logoutUser ( $user ) {
		self::resume( $this->name );
		unset( $this->loggedUsers[$user->getProperty( 'ID' )] );
	}

	public function logoutCurrentUser () {
		self::resume( $this->name );
		unset( $this->loggedUsers[$this->currentUserID] );
		$this->currentUserID = 0;
		self::redirect( 'logout' );
	}

	public function getLoggedUsers () {
		self::resume( $this->name );
		return $this->loggedUsers;
	}

	public function getCurrentUser () {
		self::resume( $this->name );
		if ( $this->currentUserID !== 0 ) {
			return $this->loggedUsers[$this->currentUserID];
		}
		return NULL;
	}

	public function setCurrentUserByID ( $user_id ) {
		self::resume( $this->name );
		if ( !empty( $user_id ) ) {
			$user_id = intval( $user_id, 10 );
			if ( array_key_exists( $user_id, $this->loggedUsers ) ) {
				$this->currentUserID = $user_id;
				return TRUE;
			}
		}
		return FALSE; // requested user is not logged in or does not exist
	}

	public function setCurrentLanguageByCode ( $languageCode ) {
		$this->currentLanguageCode = trim( $languageCode );
	}

}

?>
