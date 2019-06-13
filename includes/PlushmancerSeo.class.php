<?php
	require_once self::DIR_INCLUDES . "PlushFile.class.php";

	class PlushmancerSeo {
		public const ARTICLE_SECTION = "Plushmancer";
		public const FB_ADMINS = "otaku12";
		public const OG_TYPE = "article";
		public const TWITTER_CARD = "summary_large_image";

		private $_image = null;
		private $_revisions = null;
		private $_title = null;
		private $imageTitle = "";
		private $parser = null;

		public function __construct(Parser $parser, string $imageTitle) {
			$this->imageTitle = $imageTitle;
			$this->parser = $parser;
		}

		public function getAuthorUrl(): string { return $this->getRevisions()->current->getUserUrl(); }

		public function getDescription(): string {
			global $wgSitename;
			return wfMessage("plushiehorse-seo-description", $this->getPageName(), $wgSitename)->plain();
		}

		private function getImage(): PlushFile {
			if (!empty($this->_image))
				return $this->_image;
			return $this->_image = new PlushFile($this->imageTitle);
		}

		public function getImageAltText(): string { return wfMessage("plushiehorse-seo-image-alt-text", $this->getPageName())->plain(); }
		public function getImageHeight(): string { return $this->getImage()->getHeight(); }
		public function getImageType(): string { return $this->getImage()->getMimeType(); }
		public function getImageUrl(): string { return $this->getImage()->getUrl(); }
		public function getImageWidth(): string { return $this->getImage()->getWidth(); }
		public function getKeywords(): string { return wfMessage("plushiehorse-seo-keywords", $this->getPageName())->plain(); }

		public function getKeywordsAsTags(): array {
			return PlushieHorse::stringSplitReduce($this->getKeywords(), ",", function(\Ds\Set $result, string $tag): \Ds\Set {
				$result->add(Html::rawElement("meta", ["content" => $tag, "property" => "article:tag"]));
				return $result;
			}, new \Ds\Set())->toArray();
		}

		public function getModifiedTime(): string { return $this->getRevisions()->current->getTimestamp(); }
		public function getPageName(): string { return $this->getTitle()->getText(); }

		public function getPageTitle(): string {
			global $wgSitename;
			return wfMessage("plushiehorse-seo-page-title", $this->getPageName(), $wgSitename);
		}

		public function getPageUrl(): string { return $this->getTitle()->getCanonicalURL(); }
		public function getPublishedTime(): string { return $this->getRevisions()->first->getTimestamp(); }
		public function getPublisherUrl(): string { return $this->getRevisions()->first->getUserUrl(); }

		private function getRevisions(): \stdClass {
			if (!empty($this->_revisions))
				return $this->_revisions;
			$revisions = new \stdClass();
			$revisions->current = new PlushmancerSeoRevision($this->parser->fetchCurrentRevisionOfTitle($this->getTitle()));
			$revisions->first = new PlushmancerSeoRevision($this->getTitle()->getFirstRevision());
			$authors = array_reverse($this->getTitle()->getAuthorsBetween($revisions->first->revision, $revisions->current->revision, 1000, "include_both"));
			$authorRevisions = array_reduce($authors, function(\Ds\Set $metaAuthors, string $author): \Ds\Set {
				$user = User::newFromName($author);

				if ($user !== false && $user->getId() > 0)
					$metaAuthors->add(Html::rawElement("meta", ["content" => $user->getUserPage()->getCanonicalURL(), "itemprop" => "author", "property" => "article:author"]));
				return $metaAuthors;
			}, new \Ds\Set());

			if (!is_null($authorRevisions))
				$revisions->authors = $authorRevisions->toArray();
			return $this->_revisions = $revisions;
		}

		private function getTitle(): Title {
			if (!empty($this->_title))
				return $this->_title;
			return $this->_title = $this->parser->getTitle();
		}

		public function isAuthorAnon(): bool { return $this->getRevisions()->current->getUserUrl() === ""; }
		public function isPublisherAnon(): bool { return $this->getRevisions()->first->getUserUrl() === ""; }

		public function toArray(): array {
			if (!($this->parser->fetchCurrentRevisionOfTitle($this->getTitle()) instanceof Revision))
				return [];
			global $wgServer;
			$result = array_merge([
				Html::rawElement("meta", ["content" => $this->getPageTitle(), "itemprop" => "alternateName", "name" => "title", "property" => "og:title"]),
				Html::rawElement("meta", ["content" => $this->getDescription(), "itemprop" => "description", "name" => "description", "property" => "og:description"]),
				Html::rawElement("meta", ["content" => $this->getKeywords(), "itemprop" => "keywords", "name" => "keywords"]),
				Html::rawElement("meta", ["content" => $this->getImageUrl(), "itemprop" => "image", "name" => "twitter:image", "property" => "og:image"]),
				Html::rawElement("meta", ["content" => $this->getImageAltText(), "name" => "twitter:image:alt", "property" => "og:image:alt"]),
				Html::rawElement("meta", ["content" => $this->getImageWidth(), "property" => "og:image:width"]),
				Html::rawElement("meta", ["content" => $this->getImageHeight(), "property" => "og:image:height"]),
				Html::rawElement("meta", ["content" => $this->getImageType(), "property" => "og:image:type"]),
				Html::rawElement("meta", ["content" => self::OG_TYPE, "property" => "og:type"]),
				Html::rawElement("meta", ["content" => $this->getPageUrl(), "itemprop" => "url", "property" => "og:url"]),
				Html::rawElement("meta", ["content" => self::FB_ADMINS, "property" => "fb:admins"]),
				Html::rawElement("meta", ["content" => self::TWITTER_CARD, "name" => "twitter:card"]),
				Html::rawElement("meta", ["content" => $this->getPageTitle(), "name" => "twitter:title"]),
				Html::rawElement("meta", ["content" => $this->getDescription(), "name" => "twitter:description"]),
				Html::rawElement("meta", ["content" => $wgServer, "itemprop" => "publisher", "property" => "article:publisher"]),
				Html::rawElement("meta", ["content" => self::ARTICLE_SECTION, "property" => "article:section"]),
				Html::rawElement("meta", ["content" => "free", "property" => "article:content_tier"]),
				Html::rawElement("meta", ["content" => $this->getModifiedTime(), "itemprop" => "dateModified", "property" => "article:modified_time"]),
				Html::rawElement("meta", ["content" => $this->getPublishedTime(), "itemprop" => "dateCreated", "property" => "article:published_time"])
			], $this->getRevisions()->authors, $this->getKeywordsAsTags());
			return is_null($result) ? [] : $result;
		}
	}

	class PlushmancerSeoRevision {
		private $_timestamp = "";
		private $_userUrl = "";
		public $revision = null;

		public function __construct($revision) {
			$this->revision = $revision;
		}

		public function getId(): int { return $this->isValidRevision() ? $this->revision->getId() : 0; }

		public function getTimestamp(): string {
			if (!empty($this->_timestamp))
				return $this->_timestamp;
			$timestamp = new \DateTime($this->isValidRevision() ? $this->revision->getTimestamp() : null);
			return $this->_timestamp = $timestamp->format(\DateTime::W3C);
		}

		public function getUserUrl(): string {
			if (!$this->isValidReason())
				return $this->_userUrl = "";
			else if (!empty($this->_userUrl))
				return $this->_userUrl;
			$user = User::newFromId($this->revision->getUser());

			if ($user->isAnon())
				return $this->_userUrl = "";
			return $this->_userUrl = User::newFromId($this->revision->getUser())->getUserPage()->getCanonicalURL();
		}

		private function isValidRevision() { return !is_null($this->revision); }
	}
?>