<?php
	require_once self::DIR_INCLUDES . "HtmlEncoder.class.php";
	require_once self::DIR_INCLUDES . "PlushmancerListCache.class.php";

	class PlushmancerList {
		private const ENCODER_ID = "plushmancer_list";
		private const INLINE_SCRIPTS_ATTRIBUTES = ["id" => "PlushmancerScripts", "type" => "application/json"];
		private const SCRIPTS = [
			"Plushie", "TypeAheadBuffer", "Utils", "Polyfills", "Plushmancer/Listbox", "Plushmancer/Listbox/Collection", "Plushmancer/Listbox/Option", "Plushmancer/Listbox/OptionCollection", "Plushmancer/Sorter", "Plushmancer/Sorter/Group", 
			"Plushmancer/Sorter/GroupCollection", "Plushmancer/Table", "Plushmancer/Table/Column", "Plushmancer/Table/ColumnCollection", "Plushmancer/Table/Element", "Plushmancer/Table/Row", "Plushmancer/Table/RowCollection"
		];

		private static $cache = null;

		private static function getAllTags(): string { return self::getInlineScripts() . self::getLoaderScript() . self::getScripts() . self::getStyle(); }
		private static function getFilePath(string $name): string { return PlushieHorse::HTML_DIR . $name; }

		private static function getInlineScripts(): string {
			return self::$cache->tryFetchCheck("inlineScriptsObject", function(): string { return Html::rawElement("script", self::INLINE_SCRIPTS_ATTRIBUTES, json_encode(self::SCRIPTS)); }, crc32(serialize(self::SCRIPTS)));
		}

		private static function getLoaderScript(): string {
			return self::$cache->tryFetchCheck("loaderScriptObject", function(): string { return Html::inlineScript(file_get_contents(PlushieHorse::DIR . "js/loader.js")); }, filemtime(PlushieHorse::DIR . "js/loader.js"), "lastModifiedTime");
		}

		private static function getScriptPath(string $name): string { return self::getFilePath("js/{$name}.js"); }

		private static function getScripts(): string {
			return self::$cache->tryFetchCheck("scriptsObject", function(): string {
				return array_reduce(self::SCRIPTS, function(string $tags, string $script): string { return $tags . Html::rawElement("script", ["async" => true, "src" => self::getScriptPath($script)]); }, "");
			}, crc32(serialize(self::SCRIPTS)));
		}

		private static function getStyle(): string { return self::$cache->tryFetch("style", function(): string { return Html::linkedStyle(self::getStylePath("index")); }); }
		private static function getStylePath(string $name): string { return self::getFilePath("css/{$name}.css"); }

		public function __construct() {
			if (is_null(self::$cache))
				self::$cache = new PlushmancerListCache();
		}

		public function __toString(): string { return new HtmlEncoder(self::ENCODER_ID, base64_encode(self::getAllTags())); }
	}
// $descriptionFactory = new \SMW\Query\DescriptionFactory();
// $query = new \SMWQuery($descriptionFactory->newClassDescription(new \SMW\DIWikiPage("Plushmancer", NS_CATEGORY)));
// $query->setExtraPrintouts([new \SMWPrintRequest(\SMWPrintRequest::PRINT_PROP, "DeviantArt", \SMWPropertyValue::makeProperty("Has DeviantArt username"))]);
// $query->setLimit(5);
// $queryResult = \SMW\StoreFactory::getStore()->getQueryResult($query);
// var_dump($queryResult->getNext()[0]);


// $result = array_map(function(\SMWDataItem $item): string {
// 	if ($item instanceof \SMWDIUri)
// 		return $item->getURI();
// 	else if ($item instanceof \SMWDIWikiPage) {
// 		$result = str_replace("_", " ", $item->getDBKey());

// 		if ($item->getNamespace() != 0)
// 			$result = \MWNamespace::getCanonicalName($item->getNamespace()) . ":$result";
// 		return $result;
// 	}
// 	return str_replace("_", " ", $item->getSortKey());
// }, \SMW\StoreFactory::getStore()->getPropertyValues(null, \SMWDIProperty::newFromUserLabel("Has DeviantArt username")));
?>