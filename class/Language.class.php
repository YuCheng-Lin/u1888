<?php
	/*
	 * Autor: Deyby Vasquez
	 * email: edevd80@gmail.com
	 * Name class: Language class
	 * Licence : MIT licence
	 * Web site: http://loquenecesita.com
	 * Version: 1.1
	 *
	 * Require PHP 4 or 5
	 *
	 * This class allows to implement multiple languages to a Web site developed
	 * in PHP Language.
	 *
	 * Language class detects language browser automatically and display the Web
	 * site content in the correct language or in a default language, also allows
	 * to implement a system to change the language.
	 *
	 * Just create a file with an associative array called $lang with the pair
	 * key => value with the translattion for every locale you require inside of
	 * language directory e.g.
	 *
	 * English
	 *
	 * $lang = array (
	 *		'title' => "Page in English",
	 *		'hello' => "Hello the language page is English"
	 *	);
	 *
	 * Spanish
	 *
	 * $lang = array (
	 *		'title' => "Página en español",
	 *		'hello' => "Hola el lenguaje es español"
	 *	);
	 *
	 * *************************************************************************
	 * Default language
	 * It's important to configure a default language in order to display if the
	 * Web site doesn't support the visit language
	 * *************************************************************************
	 * The name of the locale file required as default language is the value of
	 * $DEFAULT_LANGUAGE e.g.
	 *
	 * $DEFAULT_LANGUAGE = 'en';
	 *
	 * *************************************************************************
	 * Language directory
	 * Configure path for the languages "locales" files directory
	 *
	 * $LANGUAGE_DIR = '/languages';
	 *
	 * Absolut or relative path
	 * *************************************************************************
	 */

	if(!isset($_SESSION)){
		session_start();
	}

	include_once $_SERVER['DOCUMENT_ROOT'].'/class/pub_config_admin.php';
	class Language {
        /*
         * Method to detect the locale browser automatically
         *
         * Return locale e.g es, en, de ...
         */
		function detectLanguage() {
			// if ($_SERVER['HTTP_ACCEPT_LANGUAGE']) {
			// 	$this->languages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			// 	$this->language = substr($this->languages,0,2);
			// 	return $this->language;
			// }
			// else if ($_SERVER['HTTP_USER_AGENT']) {
   //              $this->user_agent = explode(";" , $_SERVER['HTTP_USER_AGENT']);

			// 	for ($i=0; $i < sizeof($this->user_agent); $i++) {
			// 		$this->languages = explode("-",$this->user_agent[$i]);
			// 		if (sizeof($this->languages) == 2) {
			// 			if (strlen(trim($this->languages[0])) == 2) {
			// 				$size = sizeof($this->language);
			// 				$this->language[$size]=trim($this->languages[0]);
			// 			}
			// 		}
			// 	}
			// 	return $this->language[0];
			// }
			// else {
				return $this->language = DEFAULT_LANGUAGE;
			// }
		}
		/*
		 * Method to detect if a language file exists in the Language directory
		 *
		 * Return bool TRUE if exist, FALSE if not.
		 */
		function checkLanguage($language = null) {
			$language = LANGUAGE_DIR."/".$language.".conf";
			if (file_exists($language))
				return TRUE;
			else
				return FALSE;
		}
		/*
		 * Method to set language
		 */
		function setLanguage($language = null){
			if ($language)
				$_SESSION['LANGUAGE'] = $language;

			if (@!$_SESSION['LANGUAGE'])
				$_SESSION['LANGUAGE'] = $this->detectLanguage();

            if ($this->checkLanguage($_SESSION['LANGUAGE'])) {
            	$lang = $_SESSION['LANGUAGE'];
            	return $lang;
            }else {
            	return DEFAULT_LANGUAGE;
            }
		}
        /*
         * Method to get a language
         */
		function getLanguage($language = null) {
            $language = $this->setLanguage($language);
			include LANGUAGE_DIR."/".$language.".conf";
			return $lang;
		}
	}
?>