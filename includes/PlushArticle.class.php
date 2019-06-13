<?php
	class PlushArticle {
		private $_firstRevision = null;
		private $_imageFile = null;
		private $_title = null;
		private $out = null;
		private $skin = null;

		private static function copyFromObject(\stdClass $fromObject, \stdClass &$toObject): \stdClass {
			foreach ($fromObject as $key => $value)
				$toObject->{$key} = $value;
			return $toObject;
		}

		private static function getPageIdPrefix(): string { return $GLOBALS["wgServer"] . "#/articles/id/"; }

		private static function newSimpleObject(string $key, $value): \stdClass {
			$result = new \stdClass();
			$result->{$key} = $value;
			return $result;
		}

		public function __construct(OutputPage $out, Skin $skin) {
			$this->out = $out;
			$this->skin = $skin;
		}

		public function __toString(): string { return json_encode($this->toObject(), JSON_UNESCAPED_SLASHES); }

		public function getAuthors(): \stdClass {
			$firstRevision = $this->getFirstRevision();

			if (!($firstRevision instanceof Revision))
				return new \stdClass();
			$authors = new \Ds\Set(array_reverse($this->getTitle()->getAuthorsBetween($firstRevision, $this->out->getRevisionId(), 1000, "include_both")));
			$authorsArray = $authors->reduce(function(array $authors, string $authorName): array {
				$author = new \stdClass();
				$author->{"@type"} = "Person";
				$user = User::newFromName($authorName);

				if ($user instanceof User) {
					if ($user->isAnon()) {
						$author->{"@id"} = $GLOBALS["wgServer"] . "#/users/ip/" . $user->getName();
						$author->description = wfMessage("plushiehorse-article-anonymous-name")->plain();
						$author->name = $user->getName();
					} else {
						$author->{"@id"} = $user->getUserPage()->getCanonicalURL();
					}
				} else {
					$author->{"@id"} = $GLOBALS["wgServer"] . "#/users/ip/" . $authorName;
					$author->description = wfMessage("plushiehorse-article-anonymous-name")->plain();
					$author->name = $authorName;
				}
				$authors[] = $author;
				return $authors;
			}, []);
			return self::newSimpleObject("author", (count($authorsArray) === 1) ? $authorsArray[0] : $authorsArray);
		}

		private function getDateCreated(): string {
			$firstRevision = $this->getFirstRevision();

			if (!($firstRevision instanceof Revision))
				return "";
			$createdDate = new \DateTime($firstRevision->getTimestamp());
			return $createdDate->format(\DateTime::W3C);
		}

		public function getDateModified(): string {
			$modifiedDate = new \DateTime($this->getTitle()->getTouched());
			$modifiedDateFormatted = $modifiedDate->format(\DateTime::W3C);
			return empty($modifiedDateFormatted) ? $this->getDateCreated() : $modifiedDateFormatted;
		}

		public function getDescription(): string { return wfMessage("plushiehorse-article-description", $this->getTitle()->getText(), $GLOBALS["wgSitename"])->plain(); }
		public function getDiscussionUrl(): \stdClass { return $this->isContentTitle() ? self::newSimpleObject("discussionUrl", $this->getTitle()->getTalkPage()->getCanonicalURL()) : new \stdClass(); }

		private function getFirstRevision() {
			if (is_null($this->_firstRevision))
				$this->_firstRevision = $this->getTitle()->getFirstRevision();
			return $this->_firstRevision;
		}

		public function getHeadline(): string { return $this->out->getHTMLTitle() ?? ""; }
		private function getId(): string { return self::getPageIdPrefix() . strval($this->getTitle()->getArticleID()); }

		public function getImage(): \stdClass {
			$image = new \stdClass();
			$image->{"@type"} = "ImageObject";
			$imageFile = $this->getImageFile();
			$image->{"@id"} = $imageFile->isLogo ? $this->getLogoId() : self::getPageIdPrefix() . $imageFile->getArticleID();
			$image->encodingFormat = pathinfo($imageFile->getText(), PATHINFO_EXTENSION);

			if (!$imageFile->isLogo)
				$image->fileFormat = $imageFile->getMimeType();
			$image->height = $this->getPixelValue($imageFile->getHeight());
			$image->name = $imageFile->getText();
			$image->url = $imageFile->getFullURL();
			$image->width = $this->getPixelValue($imageFile->getWidth());
			return self::newSimpleObject("image", $image);
		}

		public function getImageFile() {
			if (is_null($this->_imageFile))
				$this->_imageFile = self::ImageFile($this->out->getFileSearchOptions());
			return $this->_imageFile;
		}

		private function getLogo(): \stdClass {
			$logo = new \stdClass();
			$logo->{"@id"} = $this->getLogoId();
			$logo->{"@type"} = "ImageObject";
			$logo->encodingFormat = pathinfo($GLOBALS["wgLogo"], PATHINFO_EXTENSION);
			$logo->height = $this->getPixelValue(135);
			$logo->name = basename($GLOBALS["wgLogo"]);
			$logo->url = $GLOBALS["wgServer"] . $GLOBALS["wgLogo"];
			$logo->width = $this->getPixelValue(135);
			return $logo;
		}

		private function getLogoId(): string { return $GLOBALS["wgServer"] . "/#logo"; }

		public function getMainEntityOfPage(): \stdClass {
			$mainEntityOfPage = new \stdClass();
			$mainEntityOfPage->{"@id"} = $this->getTitle()->getFullURL();
			$mainEntityOfPage->{"@type"} = "WebPage";
			$mainEntityOfPage->url = $this->getTitle()->getFullURL();
			return self::newSimpleObject("mainEntityOfPage", $mainEntityOfPage);
		}

		private function getPixelValue(int $numPixels): \stdClass {
			$pixelValue = new \stdClass();
			$pixelValue->{"@type"} = "QuantitativeValue";
			$pixelValue->unitCode = "E37";
			$pixelValue->unitText = wfMessage("plushiehorse-article-pixel")->plain();
			$pixelValue->value = $numPixels;
			return $pixelValue;
		}

		public function getPublisher(): \stdClass {
			$publisher = new \stdClass();
			$publisher->{"@type"} = "Organization";
			$publisher->logo = $this->getLogo();
			$publisher->name = $GLOBALS["wgSitename"];
			$publisher->url = $GLOBALS["wgServer"];
			return self::newSimpleObject("publisher", $publisher);
		}

		public function getTitle(): Title {
			if (is_null($this->_title)) {
				$title = $this->skin->getTitle();

				if (!($title instanceof Title))
					$title = Title::newFromID(1);
				$this->_title = $title;
			}
			return $this->_title;
		}

		public function isContentTitle(): bool { return $this->getTitle() instanceof Title && $this->getTitle()->isContentPage(); }

		public function toArray(): array {
			$categories = $this->out->getCategories();
			$result = [];

			if (!in_array("Plushmancer", $categories, true)) {
				if ($this->getTitle()->getText() === "Main Page")
					$this->out->setCanonicalUrl($GLOBALS["wgServer"]);
				$imageFile = $this->getImageFile();
				$result = [
					Html::rawElement("meta", ["content" => $this->getHeadline(), "itemprop" => "alternateName", "name" => "title", "property" => "og:title"]),
					Html::rawElement("meta", ["content" => $this->getDescription(), "itemprop" => "description", "name" => "description", "property" => "og:description"]),
					Html::rawElement("meta", ["content" => implode(",", array_merge([$this->getTitle()->getText()], $this->out->getCategories())), "itemprop" => "keywords", "name" => "keywords"]),
					Html::rawElement("meta", ["content" => $imageFile->getFullURL(), "name" => "twitter:image", "property" => "og:image"]),
					Html::rawElement("meta", ["content" => $imageFile->getWidth(), "property" => "og:image:width"]),
					Html::rawElement("meta", ["content" => $imageFile->getHeight(), "property" => "og:image:height"]),
					Html::rawElement("meta", ["content" => "article", "property" => "og:type"]),
					Html::rawElement("meta", ["content" => $this->getTitle()->getFullURL(), "itemprop" => "url", "property" => "og:url"]),
					Html::rawElement("meta", ["content" => "otaku12", "property" => "fb:admins"]),
					Html::rawElement("meta", ["content" => "summary_large_image", "name" => "twitter:card"]),
					Html::rawElement("meta", ["content" => "@CorpulentBrony", "name" => "twitter:site"]),
					Html::rawElement("meta", ["content" => $this->getHeadline(), "name" => "twitter:title"]),
					Html::rawElement("meta", ["content" => $this->getDescription(), "name" => "twitter:description"]),
					Html::rawElement("meta", ["content" => $GLOBALS["wgServer"], "itemprop" => "publisher", "property" => "article:publisher"]),
					Html::rawElement("meta", ["content" => "free", "property" => "article:content_tier"]),
					Html::rawElement("meta", ["content" => $this->getDateCreated(), "itemprop" => "dateCreated", "property" => "article:published_time"]),
					Html::rawElement("meta", ["content" => $this->getDateModified(), "itemprop" => "dateModified", "property" => "article:modified_time"])


					// <meta content="2017-09-21T18:41:25+00:00" itemprop="dateModified" property="article:modified_time">
					// <meta content="2017-09-15T04:48:20+00:00" itemprop="dateCreated" property="article:published_time">
					// <meta content="https://plushie.horse/content/User:CorpulentBrony" itemprop="author" property="article:author">
					// <meta content="Adam L. Humphreys" property="article:tag">
					// <meta content="plushie" property="article:tag">
					// <meta content="horse" property="article:tag">
					// <meta content="pony" property="article:tag">
					// <meta content="mlp" property="article:tag">
					// <meta content="artist" property="article:tag">
					// <meta content="plush maker" property="article:tag">
					// <meta content="plushmancer" property="article:tag">
					// <meta content="brony" property="article:tag">
				];

				if (!$imageFile->isLogo)
					$result[] = Html::rawElement("meta", ["content" => $imageFile->getMimeType(), "property" => "og:image:type"]);

				if (count($categories) > 0)
					$result[] = Html::rawElement("meta", ["content" => $categories[0], "property" => "article:section"]);
			}
			return array_merge($result, [
				Html::rawElement("link", ["href" => "//creativecommons.org/licenses/by-nc-sa/4.0/", "itemprop" => "license", "rel" => "code-license content-license license", "type" => "text/html"]),
				Html::rawElement("link", ["href" => "//horse.best", "rel" => "bestpony", "type" => "text/html"]),
				Html::rawElement("script", ["async" => true, "type" => "application/ld+json"], json_encode($this->toObject(), JSON_UNESCAPED_SLASHES))
			]);
		}

		public function toObject(): \stdClass {
			// was modifying this to change the type of object from Article to Person if we are in the User namespace
			$object = new \stdClass();
			$object->{"@context"} = "https://schema.org";
			$object->{"@id"} = $this->getTitle()->getFullURL();
			$object->additionalType = "https://schema.org/WebPage";
			$image = null;

			if ($this->getTitle()->inNamespace(NS_USER)) {
				$userName = $this->getTitle()->getText();
				$user = User::newFromName($userName);
				$object->{"@type"} = "Person";
				$contactPoint = new \stdClass();
				$contactPoint->{"@type"} = "ContactPoint";
				$contactPoint->contactType = "talk page";
				$contactPoint->url = $this->getTitle()->getTalkPage()->getCanonicalURL();
				self::copyFromObject(self::newSimpleObject("contactPoint", $contactPoint), $object);
				$image = $this->getImageFile()->isLogo ? new \stdClass() : $this->getImage();
				$gender = ucfirst($user->getOption("gender"));
				$object->gender = ($gender == "Male" || $gender == "Female") ? "https://schema.org/{$gender}" : "unknown";
				$object->knowsLanguage = $user->getOption("language");
			} else {
				$object->{"@type"} = "Article";
				self::copyFromObject($this->getAuthors(), $object);
				$object->dateModified = $this->getDateModified();
				$object->datePublished = $this->getDateCreated();
				self::copyFromObject($this->getDiscussionUrl(), $object);
				$object->headline = $this->getHeadline();
				$image = $this->getImage();
				self::copyFromObject($this->getPublisher(), $object);
			}
			self::copyFromObject($image, $object);
			self::copyFromObject($this->getMainEntityOfPage(), $object);
			$object->url = $this->getTitle()->getFullURL();
			return $object;
		}

		private static function ImageFile(array $imageName) {
			return new class($imageName) {
				private $_file = null;
				private $_title = null;
				public $isLogo = false;

				public function __construct(array $imageName) {
					if (!empty($imageName)) {
						$this->_title = Title::newFromText(array_keys($imageName)[0]);

						if ($this->_title instanceof Title) {
							if ($this->_title->getNamespace() != NS_FILE)
								$this->_title = Title::makeTitle(NS_FILE, $this->_title->getText());
							$this->_file = wfFindFile($this->_title);
						}
					}

					if (!($this->_file instanceof File)) {
						$this->_title = new class() {
							public function getArticleID(): string { return "logo"; }
							public function getText(): string { return basename($GLOBALS["wgLogo"]); }
						};
						$this->_file = new class() {
							public function getFullURL(): string { return $GLOBALS["wgServer"] . $GLOBALS["wgLogo"]; }
							public function getHeight(): int { return 135; }
							public function getMimeType() { return; }
							public function getWidth(): int { return 135; }
						};
						$this->isLogo = true;
					}
				}

				public function getArticleID(): string { return strval($this->_title->getArticleID()); }
				public function getFullURL(): string { return $this->_file->getFullURL(); }
				public function getHeight(): int { return $this->_file->getHeight(); }
				public function getMimeType(): string { return $this->_file->getMimeType(); }
				public function getText(): string { return $this->_title->getText(); }
				public function getWidth(): int { return $this->_file->getWidth(); }
			};
		}
	}
?>