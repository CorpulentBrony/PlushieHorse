<?php
	ini_set("allow_url_fopen", "On");
	require_once self::DIR_INCLUDES . "PlushPicCache.class.php";
	require_once self::DIR_INCLUDES . "PlushRandom.class.php";

	class PlushPic {
		private const CA_FILE = "/etc/pki/tls/certs/cacert.pem";
		private const DEFAULT_COLOR = "#bbb";
		private const DERPIBOORU_DOMAIN = "derpibooru.org";
		private const DERPIBOORU_FILTER = 56027;
		private const DERPIBOORU_IMAGE_SIZE_MAX = 1280;
		private const DERPIBOORU_PATH = "/search.json";
		private const DERPIBOORU_SEARCH = "plushie,irl,safe,-human,-animated";
		private const IMAGE_PROPERTIES = ["artist", "aspect_ratio", "height", "id", "index", "mime_type", "page", "representation", "source_url", "thumbnail", "width"];
		private const IMAGE_CAPTION_ID = "PlushMainPageImageCaption";
		private const IMAGE_TAG_ID = "PlushMainPageImageDisplay";
		private const PLUSHIE_URL = "https://plushie.horse";

		private const DERPIBOORU_IMAGE_SIZES = ["thumb_tiny" => 50, "thumb_small" => 150, "thumb" => 250, "small" => 320, "medium" => 800, "large" => self::DERPIBOORU_IMAGE_SIZE_MAX];
		private const DERPIBOORU_QUERY = ["q" => self::DERPIBOORU_SEARCH, "filter_id" => self::DERPIBOORU_FILTER];
		private const DERPIBOORU_TOP_URL = "https://" . self::DERPIBOORU_DOMAIN;

		private $image = null;
		private $page = null;
		private $pageNumber = 0;
		private $parser = null;

		private static function getDerpibooruUrl(int $pageNumber = 1): string {
			return self::DERPIBOORU_TOP_URL . self::DERPIBOORU_PATH . "?" . http_build_query(($pageNumber === 1) ? self::DERPIBOORU_QUERY : array_merge(self::DERPIBOORU_QUERY, ["page" => $pageNumber]));
		}

		public function __construct(Parser $parser) {
			$pages = PlushPicCache::fetchTotal();
			$this->parser = $parser;

			if ($pages === false) {
				PlushPicCache::clearPics();
				$this->page = $this->fetchPage(1, true);
				$pages = ($this->page->total > count($this->page->search)) ? ceil($this->page->total / count($this->page->search)) : 1;
				PlushPicCache::storeTotal($pages);
			}
			$this->pageNumber = PlushRandom::integer($pages) + 1;

			if ($this->pageNumber > 1 || !isset($this->page))
				$this->page = $this->fetchPage();

			if (!isset($this->page, $this->page->search)) {
				PlushPicCache::clearPics();
				return;
			}
			$this->image = $this->page->search[PlushRandom::integer(count($this->page->search))];
			$this->fetchColor();
		}

		public function __toString(): string {
			$imageUrl = substr($this->image->representation, 1);
			// $result = Html::rawElement("a", ["href" => $imageUrl, "id" => self::IMAGE_TAG_ID, "rel" => "external", "style" => "background-image: url(" . $imageUrl . ");", "target" => "_blank"]) . PHP_EOL;

			$result = Html::rawElement("img", [
				"alt" => wfMessage("plushiehorse-pic-alt")->inContentLanguage()->plain(), 
				"id" => self::IMAGE_TAG_ID, 
				"longdesc" => self::DERPIBOORU_TOP_URL . "/" . ($this->image->id ?? ""), 
				"sizes" => "64vw",
				"src" => $imageUrl,
				"srcset" => $this->getSrcset(),
				"style" => "background-color: {$this->image->color};"
			]);
			$result = Html::rawElement("picture", [], $result);
			$result = Html::rawElement("a", ["href" => $imageUrl, "itemprop" => "url", "rel" => "external", "target" => "_blank"], $result);
			$result .= Html::rawElement("meta", ["content" => "visual", "itemprop" => "accessMode"]);
			$result .= Html::rawElement("meta", ["content" => "visual", "itemprop" => "accessModeSufficient"]);
			$result .= Html::rawElement("meta", ["content" => wfMessage("plushiehorse-pic-alt")->inContentLanguage()->plain(), "itemprop" => "caption"]);
			$result .= Html::rawElement("link", ["href" => $this->image->representation, "itemprop" => "contentUrl"]);
			$result .= Html::rawElement("meta", ["content" => pathinfo($this->image->representation, PATHINFO_EXTENSION), "itemprop" => "encodingFormat"]);
			$result .= Html::rawElement("meta", ["content" => $this->image->mime_type, "itemprop" => "fileFormat"]);
			$result .= Html::rawElement("link", ["href" => $this->image->thumbnail, "itemprop" => "thumbnail"]);
			$result .= Html::rawElement("link", ["href" => $this->image->thumbnail, "itemprop" => "thumbnailUrl"]);
			$result .= Html::rawElement("figcaption", ["id" => self::IMAGE_CAPTION_ID], $this->getCaptionHtml());
			$result = Html::rawElement("figure", ["itemscope" => true, "itemtype" => "https://schema.org/ImageObject"], $result);
			return $this->parser->insertStripItem($result);
		}

		private function fetchColor() {
			if (isset($this->image, $this->image->color))
				return;
			else if (!isset($this->image->thumbnail))
				$this->image->thumbnail = str_replace("/large.", "/thumb_tiny.", $this->image->representation);
			$blob = file_get_contents(self::PLUSHIE_URL . substr($this->image->thumbnail, 1));
			$imagick = new \Imagick();
			$imagick->readImageBlob($blob);
			unset($blob);
			$imagick->scaleImage(1, 1, true);
			$pixel = $imagick->getImagePixelColor(0, 0);

			if (!$pixel)
				return DEFAULT_COLOR;
			$this->image->color = array_reduce(array_slice($pixel->getColor(), 0, 3), function(string $hex, int $dec): string { return $hex . dechex($dec); }, "#");
			PlushPicCache::storePage($this->pageNumber ?? 0, $this->page ?? null);
		}

		private function fetchPage(int $pageNumber = null, bool $isDefinitelyNotInCache = false): stdClass {
			if (is_null($pageNumber))
				$pageNumber = $this->pageNumber ?? 1;
			$result = $isDefinitelyNotInCache ? false : PlushPicCache::fetchPage($pageNumber);

			if ($result === false) {
				$result = json_decode(file_get_contents(self::getDerpibooruUrl($pageNumber)));

				if (!isset($result, $result->search))
					return new stdClass();
				// handle an edge case where enough images were deleted from derpibooru to lead to a page being empty
				else if ($pageNumber > 1 && count($result->search) === 0)
					return $this->fetchPage($pageNumber - 1);
				array_walk($result->search, [$this, "filterImage"]);
				PlushPicCache::storePage($pageNumber, $result);
			}
			return $result;
		}

		private function filterImage(stdClass &$image, int $index): stdClass {
			$artists = (new \Ds\Vector(explode(", ", $image->tags)))->reduce(function(\Ds\Vector $artists, string $tag): \Ds\Vector {
				if (substr($tag, 0, 7) === "artist:")
					$artists->push(substr($tag, 7));
				return $artists;
			}, new \Ds\Vector());
			$numberArtists = count($artists);

			if ($numberArtists === 0)
				$image->artist = null;
			else {
				$artists->apply(function(string $artist): string {
					$title = Title::newFromText($artist);
					return ($title instanceof Title && $title->isKnown()) ? Html::rawElement("a", ["href" => $title->getLocalURL()], $artist) : $artist;
				});

				if ($numberArtists === 1)
					$image->artist = $artists->get(0);
				else if ($numberArtists === 2)
					$image->artist = $artists->join(wfMessage("plushiehorse-list-two-item-conjunction")->inContentLanguage()->plain());
				else
					$image->artist = $artists->slice(0, $numberArtists - 1)->join(wfMessage("plushiehorse-list-separator")->inContentLanguage()->plain()) . 
						wfMessage("plushiehorse-list-seperator-final")->inContentLanguage()->plain() . $artists->get($numberArtists - 1);
			}
			$image->index = $index;
			$image->page = $this->pageNumber ?? 0;
			$image->representation = $image->representations->large;
			$image->thumbnail = $image->representations->thumb_tiny;

			foreach ($image as $key => $value)
				if (!in_array($key, self::IMAGE_PROPERTIES))
					unset($image->{$key});
			return $image;
		}

		private function getCaptionHtml(): string {
			if (!isset($this->image))
				return "";
			$derpibooru = Html::rawElement("span", ["itemprop" => "name"], "Derpibooru");
			$derpibooru = Html::rawElement("a", ["href" => self::DERPIBOORU_TOP_URL, "itemprop" => "url", "rel" => "external", "target" => "_blank"], $derpibooru);
			$derpibooru = Html::rawElement("span", ["itemprop" => "publisher", "itemscope" => true, "itemtype" => "https://schema.org/Organization"], $derpibooru);
			$hasArtist = isset($this->image->artist);
			$hasSource = !empty($this->image->source_url);
			$artist = null;
			$image = Html::rawElement("a", ["href" => self::DERPIBOORU_TOP_URL . "/" . ($this->image->id ?? ""), "itemprop" => "sameAs", "rel" => "external", "target" => "_blank"], wfMessage("plushiehorse-pic-caption-word-image")->inContentLanguage()->plain());
			$messageArgs = [];
			$source = null;

			if ($hasArtist) {
				$artist = Html::rawElement("span", ["itemprop" => "name"], $this->image->artist);
				$artist = Html::rawElement("span", ["itemprop" => "author", "itemscope" => true, "itemtype" => "https://schema.org/Person"], $artist);
			}

			if ($hasSource) {
				$source = filter_var($this->image->source_url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED);

				if ($source === false) {
					$hasSource = false;
					$source = null;
				} else
					$source = Html::rawElement("a", ["href" => $source, "itemprop" => "sameAs", "rel" => "cite external", "target" => "_blank"], wfMessage("plushiehorse-pic-caption-word-source")->inContentLanguage()->plain());
			}

			if ($hasArtist && $hasSource)
				$messageArgs = ["plushiehorse-pic-caption-artist-source", $image, $artist, $source, $derpibooru];
			else if ($hasArtist)
				$messageArgs = ["plushiehorse-pic-caption-artist", $image, $artist, $derpibooru];
			else if ($hasSource)
				$messageArgs = ["plushiehorse-pic-caption-source", $image, $source, $derpibooru];
			else
				$messageArgs = ["plushiehorse-pic-caption", $image, $derpibooru];
			return wfMessage(...$messageArgs)->inContentLanguage()->plain();
		}

		private function getSrcset(): string {
			$result = substr(array_reduce(array_keys(self::DERPIBOORU_IMAGE_SIZES), function(string $result, string $name): string {
				if ($this->image->width > self::DERPIBOORU_IMAGE_SIZES[$name])
					return "{$result}," . substr(str_replace("/large.", "/{$name}.", $this->image->representation), 1) . " " . strval(self::DERPIBOORU_IMAGE_SIZES[$name]) . "w";
				return $result;
			}, ""), 1);

			if ($this->image->width > self::DERPIBOORU_IMAGE_SIZE_MAX)
				return $result . "," . substr(str_replace("/large.", "/full.", $this->image->representation), 1) . " " . strval($this->image->width) . "w";
			return $result;
		}
	}
?>