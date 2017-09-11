<?php

class Translator extends BasicClass {

	/* Properties */

	protected $currentLanguageCode;
	protected $currentLanguageTranslations;


	/* Constructor & Destructor */

	public function __construct ( $languageCode = '' ) {
		parent::__construct( get_class( $this ) );
		//array_unshift( $this->properties_to_set, '____', '____' );

		$this->currentLanguageCode			= '';
		$this->currentLanguageTranslations	= array();

		$languageCode = trim( $languageCode );
		if ( empty( $languageCode ) || $this->setLanguageByCode( $languageCode ) === FALSE ) {
			$this->setDefaultLanguage();
		}
	}

	public function __destruct () {
		/*
		¯\_(ツ)_/¯
		*/
	}


	/* Public Methods */

	public function setDefaultLanguage () {
		global $ETC_LANGUAGE_CODES;
		if ( $this->currentLanguageCode !== $ETC_LANGUAGE_CODES[0] ) {
			$this->currentLanguageCode = $ETC_LANGUAGE_CODES[0];
			$this->currentLanguageTranslations = ( require_once( $ETC_LANGUAGE_CODES[0].'.inc.php' ) );
			return TRUE;
		}
		return FALSE;
	}

	public function setLanguageByCode ( $language_code ) {
		global $ETC_LANGUAGE_CODES;
		$language_code = trim( $language_code );
		if ( $this->currentLanguageCode !== $language_code ) {
			if ( in_array( $language_code, $ETC_LANGUAGE_CODES ) === FALSE ) {
				return FALSE;
			} else {
				$this->currentLanguageCode = $language_code;
				$this->currentLanguageTranslations = ( require_once( $language_code.'.inc.php' ) );
				return TRUE;
			}
		}
		return FALSE;
	}

	public function getTranslation ( $translation_request, $replacement = array() ) {
		$translation_request = trim( $translation_request );
		if ( empty( $translation_request ) ) { // ERROR: empty request
			return 'LANG_FAIL';
		} else if ( !array_key_exists( $translation_request, $this->currentLanguageTranslations ) ) { // ERROR: requested translation does not exist
			return "LANG_FAIL: '".$translation_request."'";
		} else if ( empty( $replacement ) ) { // CASE: no replacement(s) to be made
			return $this->currentLanguageTranslations[$translation_request];
		} else if ( is_array( $replacement ) ) { // CASE: replacement variable is an array
			return str_replace( array_keys( $replacement ), array_values( $replacement ), $this->currentLanguageTranslations[$translation_request] );
		} else { // ERROR: replacement variable is not an array
			return 'LANG_REPLACE_FAIL: $replacement is not an array';
		}
	}

	// retorna codificação JSON das traduções requisitadas por parâmetro. Wildcard '*' aceito para passar todas traduções daquele tipo (ex: 'war*')
	public function getJSONTranslations ( $translations_requests ) {
		$translations_requests = trim( $translations_requests );

		if ( $translations_requests === '*' ) { // apenas o wildcard '*' foi passado, retorna todas as traduções existentes (NÃO RECOMENDADO)
			return json_encode( $this->currentLanguageTranslations );
		} else {
			$subArray = array();

			$translations_requests_exploded = explode( ',', $translations_requests );
			foreach ( $translations_requests_exploded as $request ) {
				$request = trim( $request );
				if ( strlen( $request ) < 4 ) { // parâmetro muito pequeno: INVÁLIDO
					$subArray[$request] = 'LANG_FAIL: "'.$request.'"';
					continue;
				} else if ( strlen( $request ) === 4 && $request[3] !== '*' ) { // parâmetro INVÁLIDO
					$subArray[$request] = 'LANG_FAIL: "'.$request.'"';
					continue;
				} else if ( strlen( $request ) === 4 && $request[3] === '*' ) { // parâmetro é genérico: passar todas as traduções com aquele prefixo (se existir)
					$type = substr ( $request, 0, 3 );
					foreach ( $this->currentLanguageTranslations as $key => $request ) {
						if ( substr( $key, 0, 3 ) == $type ) {
							$subArray[$key] = $request;
						}
					}
					continue;
				} else if ( strlen( $request ) > 4 && array_key_exists( $request, $this->currentLanguageTranslations ) ) { // parâmetro é específico: passar a tradução correspondente (se existir)
					$subArray[$request] = $this->currentLanguageTranslations[$request];
					continue;
				}
			}

			return json_encode( $subArray );
		}
	}
}

?>
