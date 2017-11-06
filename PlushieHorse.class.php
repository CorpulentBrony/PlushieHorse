<?php
	// files included from includes dir: HtmlDecoder.class.php, HtmlEncoder.class.php, PlushFile.class.php, PlushPic.class.php
	// wfMessage("plushiehorse-error-hash-mismatch", $this->context, $this->property, $this->data)->inContentLanguage()->plain() (https://www.mediawiki.org/wiki/Manual:Messages_API)
	class PlushieHorse {
		public const CONTEXT = "PlushieHorse";
		public const DIR = __DIR__ . "/";
		private const DIR_INCLUDES = self::DIR . "includes/";
		public const HTML_DIR = "/extensions/PlushieHorse/";
		private static $PARSER_FUNCTIONS = ["first_rev", "image_info", "plushmancer_list", "plushmancer_seo", "plush_pic", "randomly_do", "script_ld_json"];
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
					return isset($value->property) && in_array($value->property, ["plushmancer_list", "plushmancer_seo", "script_ld_json"]);
				})->reduce(function(array $carry, HtmlTranscoder $value): array {
					if ($value->property === "plushmancer_list")
						$carry[self::CONTEXT . "::plushmancer_list::{$value->hash}"] = base64_decode($value->data);
					else if ($value->property === "plushmancer_seo")
						$carry[self::CONTEXT . "::plushmancer_seo::{$value->hash}"] = base64_decode($value->data);
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
				require_once self::DIR_INCLUDES . "PlushmancerList.class.php";
				return [new PlushmancerList(), "noparse" => true, "isHTML" => true];
			}
			return ["Sorry, can only fetch a plush pic on the [Plushmancer List] page", "noparse" => false, "isHTML" => false];
		}

		public static function parse_plushmancer_seo(Parser $parser, string $imageTitle): array {
			require_once self::DIR_INCLUDES . "PlushmancerSeo.class.php";
			return [new PlushmancerSeo($parser, $imageTitle), "noparse" => true, "isHTML" => true];
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