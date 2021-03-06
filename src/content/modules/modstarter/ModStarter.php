<?php

use UliCMS\Models\Content\Language;
use UliCMS\Utils\File;

class ModStarter extends MainClass {
	const MODULE_NAME = "modstarter";
	const MODULE_TITLE = "Modstarter";
	public function settings() {
		return Template::executeModuleTemplate ( self::MODULE_NAME, "list.php" );
	}
	public function getSettingsHeadline() {
		return self::MODULE_TITLE;
	}
	public function getSettingsLinkText() {
		return get_translation ( "open" );
	}
	public function editGet() {
		$name = Request::getVar ( "name" );
		$model = new ModStarterProjectViewModel ();
		
		if (! $name) {
			Request::redirect ( ModuleHelper::buildAdminURL ( self::MODULE_NAME ) );
		}
		$metadata = getModuleMeta ( $name );
		if (! $metadata) {
			Request::redirect ( ModuleHelper::buildAdminURL ( self::MODULE_NAME ) );
		}
		
		$model->module_folder = $name;
		$model->source = isset ( $metadata ["source"] ) ? $metadata ["source"] : "";
		$model->version = $metadata ["version"];
		$model->embeddable = (isset ( $metadata ["embed"] ) and $metadata ["embed"]);
		$model->shy = (isset ( $metadata ["shy"] ) and $metadata ["shy"]);
		$model->main_class = $metadata ["main_class"];
		$model->create_post_install_script = file_exists ( Path::resolve ( "ULICMS_DATA_STORAGE_ROOT/post-install.php" ) );
		$model->hooks = is_array ( $metadata ["hooks"] ) ? $metadata ["hooks"] : array ();
		$model->edit = true;
		$model->languages = array ();
		foreach ( Language::getAllLanguages () as $language ) {
			$langFile = ModuleHelper::buildRessourcePath ( $name, "lang/" . $language->getLanguageCode () . ".php" );
			if (is_file ( $langFile )) {
				$model->languages [] = $language->getLanguageCode ();
			}
		}
		
		ViewBag::set ( "model", $model );
	}
	public function updatePost() {
		if (! Request::hasVar ( "module_folder" ) or ! Request::hasVar ( "version" ) or ! Request::hasVar ( "main_class" )) {
			Request::redirect ( ModuleHelper::buildActionURL ( "modstarter_new" ) );
		}
		$module_folder = basename ( Request::getVar ( "module_folder" ) );
		if ($module_folder == "." or $module_folder == "..") {
			Request::redirect ( ModuleHelper::buildActionURL ( "modstarter_new" ) );
		}
		$version = Request::getVar ( "version" );
		$source = Request::getVar ( "source" );
		$embeddable = Request::hasVar ( "embeddable" );
		$shy = Request::hasVar ( "shy" );
		$create_post_install_script = (Request::hasVar ( "create_post_install_script" ) and ! file_exists ( Path::resolve ( "ULICMS_DATA_STORAGE_ROOT/post-install.php" ) ));
		
		$moduleFolderPath = getModulePath ( $module_folder, false );
		if (! file_exists ( $moduleFolderPath )) {
			Request::redirect ( ModuleHelper::buildActionURL ( "modstarter_new" ) );
		}
		$baseDirs = array (
				ModuleHelper::buildRessourcePath ( $module_folder, "controllers" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "objects" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "templates" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "lang" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "sql" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "sql/up" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "sql/down" ) 
		);
		foreach ( $baseDirs as $dir ) {
			if (! file_exists ( $dir )) {
				mkdir ( $dir );
			}
		}
		
		$metadataFile = ModuleHelper::buildRessourcePath ( $module_folder, "metadata.json" );
		$metadata = array ();
		if (file_exists ( $metadataFile )) {
			$metadata = getModuleMeta ( $module_folder );
		}
		if (StringHelper::isNotNullOrWhitespace ( $version )) {
			$metadata ["version"] = $version;
		}
		if (StringHelper::isNotNullOrWhitespace ( $source )) {
			$metadata ["source"] = $source;
		}
		$metadata ["embed"] = $embeddable;
		$metadata ["shy"] = $shy;
		
		$manager = new ModStarterProjectManager ();
		
		File::write ( $metadataFile, json_readable_encode ( $metadata, 0, true ) );
		File::write ( ModuleHelper::buildRessourcePath ( $module_folder, ".modstarter" ), "" );
		if ($create_post_install_script) {
			$script = Path::resolve ( "ULICMS_DATA_STORAGE_ROOT/post-install.php" );
			File::write ( $script, "<?php\r\n" );
		}
		$languages = Request::getVar ( "languages" );
		
		foreach ( Language::getAllLanguages () as $language ) {
			$langFile = ModuleHelper::buildRessourcePath ( $module_folder, "lang/" . $language->getLanguageCode () . ".php" );
			
			if (in_array ( $language->getLanguageCode (), $languages ) and ! is_file ( $langFile )) {
				file_put_contents ( $langFile, "<?php\r\n" );
			} else if (! in_array ( $language->getLanguageCode (), $languages ) and is_file ( $langFile )) {
				unlink ( $langFile );
			}
		}
		
		Request::redirect ( ModuleHelper::buildAdminURL ( self::MODULE_NAME ) );
	}
	public function createPost() {
		if (! Request::hasVar ( "module_folder" ) or ! Request::hasVar ( "version" ) or ! Request::hasVar ( "main_class" )) {
			Request::redirect ( ModuleHelper::buildActionURL ( "modstarter_new" ) );
		}
		$module_folder = basename ( Request::getVar ( "module_folder" ) );
		if ($module_folder == "." or $module_folder == "..") {
			Request::redirect ( ModuleHelper::buildActionURL ( "modstarter_new" ) );
		}
		$version = Request::getVar ( "version" );
		$source = Request::getVar ( "source" );
		$embeddable = Request::hasVar ( "embeddable" );
		$shy = Request::hasVar ( "shy" );
		$main_class = Request::getVar ( "main_class" );
		
		$create_post_install_script = Request::hasVar ( "create_post_install_script" );
		$hooks = Request::hasVar ( "hooks" ) ? Request::getVar ( "hooks" ) : array ();
		// Modul erstellen oder updaten, sofern es schon existiert und eine modstarter Datei hat
		
		if (class_exists ( $main_class )) {
			ExceptionResult ( get_translation ( "the_class_x_already_exists", array (
					"%class%" => _esc ( $main_class ) 
			) ) );
		}
		
		$moduleFolderPath = getModulePath ( $module_folder, false );
		if (! file_exists ( $moduleFolderPath )) {
			mkdir ( $moduleFolderPath );
		}
		$baseDirs = array (
				ModuleHelper::buildRessourcePath ( $module_folder, "controllers" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "objects" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "templates" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "lang" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "js" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "css" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "objects" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "sql" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "sql/up" ),
				ModuleHelper::buildRessourcePath ( $module_folder, "sql/down" ) 
		);
		foreach ( $baseDirs as $dir ) {
			if (! file_exists ( $dir )) {
				mkdir ( $dir );
			}
			/*
			 * $keepFile = $dir . "/.keep";
			 * if (! file_exists ( $keepFile )) {
			 * File::write ( $keepFile, "" );
			 * }
			 */
		}
		
		$metadataFile = ModuleHelper::buildRessourcePath ( $module_folder, "metadata.json" );
		$metadata = array ();
		if (file_exists ( $metadataFile )) {
			$metadata = getModuleMeta ( $metadataFile );
		}
		if (StringHelper::isNotNullOrWhitespace ( $version )) {
			$metadata ["version"] = $version;
		}
		if (StringHelper::isNotNullOrWhitespace ( $source )) {
			$metadata ["source"] = $source;
		}
		$metadata ["embed"] = $embeddable;
		$metadata ["shy"] = $shy;
		
		$languages = Request::getVar ( "languages" );
		if (is_array ( $languages )) {
			foreach ( $languages as $language ) {
				$langFile = ModuleHelper::buildRessourcePath ( $module_folder, "lang/{$language}.php" );
				if (! is_file ( $langFile )) {
					file_put_contents ( $langFile, "<?php\r\n" );
				}
			}
		}
		
		$manager = new ModStarterProjectManager ();
		
		if (StringHelper::isNotNullOrWhitespace ( $main_class )) {
			$metadata ["main_class"] = $main_class;
			$metadata ["controllers"] = array (
					$main_class => "controllers/" . $main_class . ".php" 
			);
			$hooksCode = "";
			if ($embeddable) {
				$hooksCode .= "public function render() {\r\n\t\treturn \"\";\r\n\t}\r\n";
			}
			if (is_array ( $hooks )) {
				foreach ( $hooks as $hook ) {
					$hooksCode .= "\tpublic function $hook(){\r\n\t\t\r\n\t}\r\n";
				}
			}
			
			$mainClassCode = $manager->prepareMainClass ( array (
					"MainClass" => $main_class,
					"ModuleName" => str_replace ( "\"", "\\\"", $module_folder ),
					"Hooks" => $hooksCode 
			) );
			$mainClassFile = ModuleHelper::buildRessourcePath ( $module_folder, "controllers/" . $main_class . ".php" );
			File::write ( $mainClassFile, $mainClassCode );
		}
		File::write ( $metadataFile, json_readable_encode ( $metadata, 0, true ) );
		File::write ( ModuleHelper::buildRessourcePath ( $module_folder, ".modstarter" ), "" );
		if ($create_post_install_script) {
			$script = Path::resolve ( "ULICMS_DATA_STORAGE_ROOT/post-install.php" );
			File::write ( $script, "<?php\r\n" );
		}
		Request::redirect ( ModuleHelper::buildAdminURL ( self::MODULE_NAME ) );
	}
}
