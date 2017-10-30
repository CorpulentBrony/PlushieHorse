<?php
	// files included from includes dir: HtmlDecoder.class.php, HtmlEncoder.class.php, PlushFile.class.php, PlushPic.class.php
	// wfMessage("plushiehorse-error-hash-mismatch", $this->context, $this->property, $this->data)->inContentLanguage()->plain() (https://www.mediawiki.org/wiki/Manual:Messages_API)
	class PlushieHorse {
		public const CONTEXT = "PlushieHorse";
		private const DIR = __DIR__ . "/";
		private const DIR_INCLUDES = self::DIR . "includes/";
		private const HTML_DIR = "/extensions/PlushieHorse/";
		private static $PARSER_FUNCTIONS = ["first_rev", "image_info", "plushmancer_list", "plush_pic", "randomly_do", "script_ld_json"];
		/**
		 * @var \Ds\Set
		 */
		private static $data = null;

		private function __construct() {}

		public static function error(string $message): string {
			return "<div class=\"errorbox\">{$message}</div>";
		}

		public static function init(Parser &$parser) {
			if (!isset(self::$data))
				self::$data = new \Ds\Set();
			array_walk(self::$PARSER_FUNCTIONS, function(string $tag, int $index, Parser $parser) { $parser->setFunctionHook($tag, [__CLASS__, "parse_{$tag}"]); }, $parser);
		}

		public static function onBeforePageDisplay(OutputPage $out, Skin &$skin) {
			$out->addHeadItems(
				self::$data->filter(function(HtmlTranscoder $value): bool {
					return isset($value->property) && in_array($value->property, ["plushmancer_list", "script_ld_json"]);
				})->reduce(function(array $carry, HtmlTranscoder $value): array {
					if ($value->property === "plushmancer_list")
						$carry[self::CONTEXT . "::plushmancer_list::{$value->hash}"] = base64_decode($value->data);
					else if ($value->property === "script_ld_json")
						$carry[self::CONTEXT . "::script_ld_json::{$value->hash}"] = "<script async type=\"application/ld+json\">{$value->data}</script>";
					return $carry;
				}, [])
			);
		}

		public static function onOutputPageBeforeHTML(OutputPage $out, string &$page) {
			require_once self::DIR_INCLUDES . "HtmlDecoder.class.php";
			$token = "\r\n";
			$line = strtok($page, $token);

			while ($line !== false) {
				if (strpos($line, "\"context\":\"" . self::CONTEXT . "\"", 5) !== false)
					self::$data->add(new HtmlDecoder($line));
				$line = strtok($token);
			}
			// free memory by resetting strtok
			strtok("", "");
		}

		public static function parse_first_rev(Parser $parser, string $name, string $property): string {
			require_once self::DIR_INCLUDES . "PlushTitle.class.php";
			$title = new PlushTitle($name);
			return $title->getProperty($property);
		}

		public static function parse_image_info(Parser $parser, string $name, string $property): string {
			require_once self::DIR_INCLUDES . "PlushFile.class.php";
			$file = new PlushFile($name);
			return $file->getProperty($property);
		}

		public static function parse_plush_pic(Parser $parser): array {
			if ($parser->getTitle()->getText() === "Main Page") {
				require_once self::DIR_INCLUDES . "PlushPic.class.php";
				return [new PlushPic($parser), "noparse" => true, "isHTML" => true];
			}
			return ["Sorry, can only fetch a plush pic on the [Main Page]", "noparse" => false, "isHTML" => false];
		}

		public static function parse_plushmancer_list(Parser $parser): array {
			if ($parser->getTitle()->getText() === "Plushmancer List") {


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
				require_once self::DIR_INCLUDES . "HtmlEncoder.class.php";
				$path = "/extensions/PlushieHorse/";
				$scripts = [
					"Plushie", "TypeAheadBuffer", "Utils", "Polyfills", "Plushmancer/Listbox", "Plushmancer/Listbox/Collection", "Plushmancer/Listbox/Option", "Plushmancer/Listbox/OptionCollection", "Plushmancer/Sorter", 
					"Plushmancer/Sorter/Group", "Plushmancer/Sorter/GroupCollection", "Plushmancer/Table", "Plushmancer/Table/Column", "Plushmancer/Table/ColumnCollection", "Plushmancer/Table/Element", "Plushmancer/Table/Row", 
					"Plushmancer/Table/RowCollection"
				];
				$encoded = new HtmlEncoder("plushmancer_list", base64_encode(
					Html::rawElement("script", ["id" => "PlushmancerScripts", "type" => "application/json"], json_encode($scripts))
					. Html::inlineScript(file_get_contents(self::DIR . "js/loader.js"))
					. array_reduce($scripts, function(string $tags, string $script): string { return $tags . Html::rawElement("script", ["async" => true, "src" => self::HTML_DIR . "js/{$script}.js"]); }, "")
					. Html::linkedStyle("{$path}css/index.css")
				));
				return [$encoded, "noparse" => true, "isHTML" => true];
			}
			return ["Sorry, can only fetch a plush pic on the [Plushmancer List] page", "noparse" => false, "isHTML" => false];
		}

		public static function parse_randomly_do(Parser $parser, string $text, string $probabilityString = "0.5"): array {
			$probability = min(max(intval(round(floatval($probabilityString) * 100)), 0), 100);
			$result = ["isHTML" => false, "noparse" => false];

			if ($probability === 0)
				$result[] = "";
			else if ($probability === 1)
				$result[] = $text;
			else {
				require_once self::DIR_INCLUDES . "PlushRandom.class.php";
				$random = PlushRandom::integer(100);
				$result[] = ($random <= $probability - 1) ? $text : "";
			}
			return $result;
		}

		public static function parse_script_ld_json(Parser $parser, string ...$data): array {
			require_once self::DIR_INCLUDES . "HtmlEncoder.class.php";
			return [new HtmlEncoder("script_ld_json", implode("|", $data)), "noparse" => true, "isHTML" => true];
		}
	}
	// \SMW\StoreFactory::getStore()->getPropertyValues(null, SMWDIProperty::newFromUserLabel("Has DeviantArt username"))
	// $values = self::getSMWPropertyValues( $store, null, $property_name, $requestoptions );
?>