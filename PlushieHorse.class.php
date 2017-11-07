<?php
	// files included from includes dir: HtmlDecoder.class.php, HtmlEncoder.class.php, PlushFile.class.php, PlushPic.class.php
	// wfMessage("plushiehorse-error-hash-mismatch", $this->context, $this->property, $this->data)->inContentLanguage()->plain() (https://www.mediawiki.org/wiki/Manual:Messages_API)
	class PlushieHorse {
		public const CONTEXT = "PlushieHorse";
		public const DIR = __DIR__ . "/";
		private const DIR_INCLUDES = self::DIR . "includes/";
		public const HTML_DIR = "/extensions/PlushieHorse/";
		private static $PARSER_FUNCTIONS = ["first_rev", "image_info", "plushmancer_list", "plushmancer_seo", "plush_pic", "randomly_do", "script_ld_json", "set_body_itemtype"];
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
			return [wfMessage("plushiehorse-plushmancer-pic-error")->inContentLanguage()->text(), "noparse" => false, "isHTML" => false];
		}

		public static function parse_plushmancer_list(Parser $parser): string {
			if ($parser->getTitle()->getText() === "Plushmancer List") {
				require_once self::DIR_INCLUDES . "PlushmancerList.class.php";
				global $wgHooks;
				$list = new PlushmancerList();
				$wgHooks["BeforePageDisplay"][] = [function(array $list, OutputPage $out, Skin &$skin) { $out->addHeadItems($list); }, $list->toArray()];
				return "";
			}
			return wfMessage("plushiehorse-plushmancer-list-error")->inContentLanguage()->text();
		}

		public static function parse_plushmancer_seo(Parser $parser, string $imageTitle): string {
			require_once self::DIR_INCLUDES . "PlushmancerSeo.class.php";
			global $wgHooks;
			$seo = new PlushmancerSeo($parser, $imageTitle);
			$wgHooks["BeforePageDisplay"][] = [function(array $tags, OutputPage $out, Skin &$skin) { $out->addHeadItems($tags); }, $seo->toArray()];
			return "";
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

		public static function parse_script_ld_json(Parser $parser, string ...$data): string {
			global $wgHooks;
			$wgHooks["BeforePageDisplay"][] = [function(array $data, OutputPage $out, Skin &$skin) {
				$out->addHeadItems(Html::rawElement("script", ["async" => true, "type" => "application/ld+json"], implode("|", $data)));
			}, $data];
			return "";
		}

		public static function parse_set_body_itemtype(Parser $parser, string $itemtype): string {
			global $wgHooks;
			$wgHooks["OutputPageBodyAttributes"][] = [function(string $itemtype, OutputPage $out, Skin $sk, array &$bodyAttrs) {
				$bodyAttrs["itemscope"] = true;
				$bodyAttrs["itemtype"] = $itemtype;
			}, $itemtype];
			return "";
		}
	}
	// \SMW\StoreFactory::getStore()->getPropertyValues(null, SMWDIProperty::newFromUserLabel("Has DeviantArt username"))
	// $values = self::getSMWPropertyValues( $store, null, $property_name, $requestoptions );
?>