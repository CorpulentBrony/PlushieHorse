<?php
	// files included from includes dir: HtmlDecoder.class.php, HtmlEncoder.class.php, PlushFile.class.php, PlushPic.class.php
	// wfMessage("plushiehorse-error-hash-mismatch", $this->context, $this->property, $this->data)->inContentLanguage()->plain() (https://www.mediawiki.org/wiki/Manual:Messages_API)
	class PlushieHorse {
		public const CONTEXT = "PlushieHorse";
		public const DIR = __DIR__ . "/";
		private const DIR_INCLUDES = self::DIR . "includes/";
		public const HTML_DIR = "/extensions/PlushieHorse/";
		private const PARSER_FUNCTIONS = ["first_rev", "image_info", "plushmancer_list", "plushmancer_seo", "plush_pic", "randomly_do", "script_ld_json", "set_body_itemtype"];
		private const PARSER_TAGS = ["meter", "nav"];
		private const VALID_GLOBAL_ATTRIBUTE_PREFIXES = ["aria-", "data-"];
		private const VALID_GLOBAL_ATTRIBUTES = ["class", "dir", "hidden", "id", "itemid", "itemprop", "itemref", "itemscope", "itemtype", "lang", "style", "title", "translate"];
		private const VALID_METER_ATTRIBUTES = ["high", "low", "max", "min", "optimum", "value"];

		private function __construct() {}

		public static function array_all(array $array, callable $callback, ...$params): bool {
			foreach ($array as $key => $value)
				if (!$callback($value, $key, ...$params))
					return false;
			return true;
		}

		public static function error(string $message): string { return "<div class=\"errorbox\">{$message}</div>"; }

		public static function init(Parser &$parser) {
			array_walk(array_merge(self::PARSER_FUNCTIONS), function(string $tag, int $index, Parser $parser) { $parser->setFunctionHook($tag, [__CLASS__, "parse_{$tag}"]); }, $parser);
			array_walk(array_merge(self::PARSER_TAGS), function(string $tag, int $index, Parser $parser) { $parser->setHook($tag, [__CLASS__, "parse_tag_{$tag}"]); }, $parser);
		}

		private static function makeParametersSafe(array $params): array { return array_map(function(string $value): string { return htmlspecialchars($value); }, $params); }

		public static function onBeforePageDisplay(OutputPage $out, Skin $skin) {
			require_once self::DIR_INCLUDES . "PlushArticle.class.php";
			// $out->addHeadItems(Html::rawElement("link", ["href" => "//creativecommons.org/licenses/by-nc-sa/4.0/", "itemprop" => "license", "rel" => "code-license content-license license", "type" => "text/html"]));
			// $out->addHeadItems(Html::rawElement("link", ["href" => "//horse.best", "rel" => "bestpony", "type" => "text/html"]));
			// $out->addHeadItems(Html::rawElement("script", ["async" => true, "type" => "application/ld+json"], strval(new PlushArticle($out, $skin))));
			$plushArticle = new PlushArticle($out, $skin);
			$out->addHeadItems($plushArticle->toArray());
		}

		public static function parse_article_json(Parser $parser): string {
			// i want this to be moved to a straight up hook that'll load on every *content page*
			global $wgHooks;
			$wgHooks["BeforePageDisplay"][] = function(OutputPage $out, Skin $skin) {
				require_once self::DIR_INCLUDES . "PlushArticle.class.php";
				$test = new PlushArticle($out, $skin);
				var_dump((string) $test);
			};
			return "";
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
				return [new PlushPic($parser), "isHTML" => true, "markerType" => "nowiki", "noparse" => true];
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

		public static function parse_tag_meter(string $content = "", array $params = [], Parser $parser): array {
			return [Html::rawElement("meter", self::makeParametersSafe($params), htmlspecialchars($content)), "isHTML" => true, "markerType" => "nowiki", "noparse" => true];
		}

		public static function parse_tag_nav(string $content = "", array $params = [], Parser $parser): array {
			if ($parser->getTitle()->getText() === "Main Page") {
				return [Html::rawElement("nav", self::makeParametersSafe($params), $content), "isHTML" => true, "markerType" => "nowiki", "noparse" => true];
			}
			return [$content];
		}

		public static function stringSplitReduce(string $string, string $delimiter, callable $callback, $initialValue = null) {
			$item = strtok($string, $delimiter);

			while ($item !== false) {
				$initialValue = is_null($initialValue) ? $item : $callback($initialValue, $item);
				$item = strtok($delimiter);
			}
			// free memory by resetting strtok
			strtok("", "");
			return $initialValue;
		}
	}
	// \SMW\StoreFactory::getStore()->getPropertyValues(null, SMWDIProperty::newFromUserLabel("Has DeviantArt username"))
	// $values = self::getSMWPropertyValues( $store, null, $property_name, $requestoptions );
?>