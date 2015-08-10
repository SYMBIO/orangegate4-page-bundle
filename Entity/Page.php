<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Sonata\PageBundle\Model\PageBlockInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="page__page")
 */
class Page implements PageInterface
{

    /**
     * @var integer $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(name="route_name", type="string", length=255)
     */
    protected $routeName;

    /**
     * @ORM\Column(name="page_alias", type="string", length=255, nullable=true)
     */
    protected $pageAlias;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $type;

    /**
     * @ORM\Column(name="request_method", type="string", length=255, nullable=true)
     */
    protected $requestMethod;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $javascript;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $stylesheet;

    /**
     * @ORM\Column(name="raw_headers", type="text", nullable=true)
     */
    protected $rawHeaders;

    protected $headers;

    /**
     * @ORM\Column(name="template", type="string", length=255, nullable=true)
     */
    protected $templateCode;

    /**
     * @ORM\Column(type="integer")
     */
    protected $position = 1;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $decorate = true;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $edited;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @Gedmo\Translatable
     */
    protected $enabled = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Translatable
     */
    protected $slug;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Translatable
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="custom_url", type="text", nullable=true)
     * @Gedmo\Translatable
     */
    protected $customUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_keyword", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $metaKeyword;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_description", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    protected $metaDescription;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Page", mappedBy="parent", cascade={"all"})
     * @ORM\OrderBy({"position":"ASC"})
     */
    protected $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Page", mappedBy="target")
     */
    protected $sources;

    /**
     * @var \Symbio\OrangeGate\PageBundle\Entity\Site
     * @ORM\ManyToOne(targetEntity="Site", cascade={"persist"})
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $site;

    /**
     * @var \Symbio\OrangeGate\PageBundle\Entity\Page
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parent;

    protected $parents;

    /**
     * @var \Symbio\OrangeGate\PageBundle\Entity\Page
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="sources", cascade={"persist"})
     * @ORM\JoinColumn(name="target_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $target;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Block", mappedBy="page", cascade={"remove","persist","refresh","merge","detach"})
     */
    protected $blocks;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Snapshot", mappedBy="page", cascade={"remove","persist","refresh","merge","detach"})
     */
    protected $snapshots;

    /**
     * @var \Symbio\OrangeGate\PageBundle\Entity\Page
     * @ORM\Column(name="description", type="text", length=20000, nullable=true)
     * @Gedmo\Translatable
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="Symbio\OrangeGate\MediaBundle\Entity\Media", cascade={"remove","persist","refresh","merge","detach"})
     * @ORM\JoinColumn(name="icon_id", referencedColumnName="id", nullable=true)
     */
    protected $icon;

    /**
     * @ORM\OneToMany(targetEntity="PageTranslation", mappedBy="object", indexBy="locale", cascade={"all"}, orphanRemoval=true)
     * @Assert\Valid
     */
    protected $translations;

    protected static $slugifyMethod;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->blocks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->snapshots = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sources = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->routeName     = PageInterface::PAGE_ROUTE_CMS_NAME;
        $this->requestMethod = 'GET|POST|HEAD|DELETE|PUT';
        $this->edited        = true;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime;
        $this->updatedAt = new \DateTime;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add children
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Page $children
     * @return Page
     */
    public function addChild(PageInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addChildren(PageInterface $children)
    {
        $this->children[] = $children;

        $children->setParent($this);
    }

    /**
     * Remove children
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Page $children
     */
    public function removeChild(PageInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * Add sources
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Page $sources
     * @return Page
     */
    public function addSource(PageInterface $sources)
    {
        $this->sources[] = $sources;

        return $this;
    }

    /**
     * Remove sources
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Page $sources
     */
    public function removeSource(PageInterface $sources)
    {
        $this->sources->removeElement($sources);
    }

    /**
     * Get sources
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * Set site
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Site $site
     * @return Page
     */
    public function setSite(SiteInterface $site = null)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Get site
     *
     * @return \Symbio\OrangeGate\PageBundle\Entity\Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set parent
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Page $parent
     * @return Page
     */
    public function setParent(PageInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Symbio\OrangeGate\PageBundle\Entity\Page
     */
    public function getParent($level = -1)
    {
        return $this->parent;
    }

    /**
     * Set target
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Page $target
     * @return Page
     */
    public function setTarget(PageInterface $target = null)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return \Symbio\OrangeGate\PageBundle\Entity\Page
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Page
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set image
     *
     * @param \Symbio\OrangeGate\MediaBundle\Entity\Media $image
     * @return Page
     */
    public function setImage(\Symbio\OrangeGate\MediaBundle\Entity\Media $image = null)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return \Symbio\OrangeGate\MediaBundle\Entity\Media
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set icon
     *
     * @param \Symbio\OrangeGate\MediaBundle\Entity\Media $icon
     * @return Page
     */
    public function setIcon(\Symbio\OrangeGate\MediaBundle\Entity\Media $icon = null)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return \Symbio\OrangeGate\MediaBundle\Entity\Media
     */
    public function getIcon()
    {
        return $this->icon;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(\Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            $translation->setObject($this);
            $this->translations->set($translation->getLocale(), $translation);
        }
        return $this;
    }

    public function removeTranslation(\Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation $translation)
    {
        if ($this->translations->contains($translation)) {
            $this->translations->removeElement($translation);
        }
        return $this;
    }

    public function getTranslation($locale)
    {
        if (isset($this->translations[$locale])) {
            return $this->translations[$locale];
        }

        return null;
    }

    /**
     * Add blocks
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Block $blocks
     * @return Page
     */
    public function addBlock(PageBlockInterface $blocks)
    {
        $this->blocks[] = $blocks;

        return $this;
    }

    /**
     * Add blocs
     *
     * @param PageBlockInterface $blocs
     */
    public function addBlocks(PageBlockInterface $blocs)
    {
        $blocs->setPage($this);

        $this->blocks[] = $blocs;
    }

    /**
     * Remove blocks
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Block $blocks
     */
    public function removeBlock(\Symbio\OrangeGate\PageBundle\Entity\Block $blocks)
    {
        $this->blocks->removeElement($blocks);
    }

    /**
     * Get blocks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Page
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Page
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set routeName
     *
     * @param string $routeName
     * @return Page
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * Get routeName
     *
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * Set pageAlias
     *
     * @param string $pageAlias
     * @return Page
     */
    public function setPageAlias($pageAlias)
    {
        $this->pageAlias = $pageAlias;

        return $this;
    }

    /**
     * Get pageAlias
     *
     * @return string
     */
    public function getPageAlias()
    {
        return $this->pageAlias;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Page
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set requestMethod
     *
     * @param string $requestMethod
     * @return Page
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;

        return $this;
    }

    /**
     * Get requestMethod
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Set javascript
     *
     * @param string $javascript
     * @return Page
     */
    public function setJavascript($javascript)
    {
        $this->javascript = $javascript;

        return $this;
    }

    /**
     * Get javascript
     *
     * @return string
     */
    public function getJavascript()
    {
        return $this->javascript;
    }

    /**
     * Set stylesheet
     *
     * @param string $stylesheet
     * @return Page
     */
    public function setStylesheet($stylesheet)
    {
        $this->stylesheet = $stylesheet;

        return $this;
    }

    /**
     * Get stylesheet
     *
     * @return string
     */
    public function getStylesheet()
    {
        return $this->stylesheet;
    }

    /**
     * Set rawHeaders
     *
     * @param string $rawHeaders
     * @return Page
     */
    public function setRawHeaders($rawHeaders)
    {
        $headers = $this->getHeadersAsArray($rawHeaders);

        $this->setHeaders($headers);
    }

    /**
     * Get rawHeaders
     *
     * @return string
     */
    public function getRawHeaders()
    {
        return $this->rawHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function addHeader($name, $header)
    {
        $headers = $this->getHeaders();

        $headers[$name] = $header;

        $this->headers = $headers;

        $this->rawHeaders = $this->getHeadersAsString($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaders(array $headers = array())
    {
        $this->headers = array();
        $this->rawHeaders = null;
        foreach ($headers as $name => $header) {
            $this->addHeader($name, $header);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        if (null === $this->headers) {
            $rawHeaders = $this->getRawHeaders();
            $this->headers = $this->getHeadersAsArray($rawHeaders);
        }

        return $this->headers;
    }

    /**
     * Set templateCode
     *
     * @param string $templateCode
     * @return Page
     */
    public function setTemplateCode($templateCode)
    {
        $this->templateCode = $templateCode;

        return $this;
    }

    /**
     * Get templateCode
     *
     * @return string
     */
    public function getTemplateCode()
    {
        return $this->templateCode;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return Page
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set decorate
     *
     * @param boolean $decorate
     * @return Page
     */
    public function setDecorate($decorate)
    {
        $this->decorate = $decorate;

        return $this;
    }

    /**
     * Get decorate
     *
     * @return boolean
     */
    public function getDecorate()
    {
        return $this->decorate;
    }

    /**
     * Set edited
     *
     * @param boolean $edited
     * @return Page
     */
    public function setEdited($edited)
    {
        $this->edited = $edited;

        return $this;
    }

    /**
     * Get edited
     *
     * @return boolean
     */
    public function getEdited()
    {
        return $this->edited;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return Page
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Page
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Page
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Page
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Page
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set customUrl
     *
     * @param string $customUrl
     * @return Page
     */
    public function setCustomUrl($customUrl)
    {
        $this->customUrl = $customUrl;

        return $this;
    }

    /**
     * Get customUrl
     *
     * @return string
     */
    public function getCustomUrl()
    {
        return $this->customUrl;
    }

    /**
     * Set metaKeyword
     *
     * @param string $metaKeyword
     * @return Page
     */
    public function setMetaKeyword($metaKeyword)
    {
        $this->metaKeyword = $metaKeyword;

        return $this;
    }

    /**
     * Get metaKeyword
     *
     * @return string
     */
    public function getMetaKeyword()
    {
        return $this->metaKeyword;
    }

    /**
     * Set metaDescription
     *
     * @param string $metaDescription
     * @return Page
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /**
     * Get metaDescription
     *
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRequestMethod($method)
    {
        $method = strtoupper($method);

        if (!in_array($method, array('PUT', 'POST', 'GET', 'DELETE', 'HEAD'))) {
            return false;
        }

        return !$this->getRequestMethod() || false !== strpos($this->getRequestMethod(), $method);
    }

    /**
     * {@inheritdoc}
     */
    public function setParents(array $parents)
    {
        $this->parents = $parents;
    }

    /**
     * {@inheritdoc}
     */
    public function getParents()
    {
        if (!$this->parents) {

            $page    = $this;
            $parents = array();

            while ($page->getParent()) {
                $page      = $page->getParent();
                $parents[] = $page;
            }

            $this->setParents(array_reverse($parents));
        }

        return $this->parents;
    }

    /**
     * {@inheritdoc}
     */
    public function isHybrid()
    {
        return $this->getRouteName() != self::PAGE_ROUTE_CMS_NAME  && !$this->isInternal();
    }

    /**
     * {@inheritdoc}
     */
    public function isCms()
    {
        return $this->getRouteName() == self::PAGE_ROUTE_CMS_NAME && !$this->isInternal();
    }

    /**
     * {@inheritdoc}
     */
    public function isInternal()
    {
        return substr($this->getRouteName(), 0, 15) == '_page_internal_';
    }

    /**
     * {@inheritdoc}
     */
    public function isDynamic()
    {
        return $this->isHybrid() && strpos($this->getUrl(), '{') !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function isError()
    {
        return substr($this->getRouteName(), 0, 21) == '_page_internal_error_';
    }

    public function disableBlockLazyLoading()
    {
        if (is_object($this->blocks)) {
            $this->blocks->setInitialized(true);
        }
    }

    public function disableChildrenLazyLoading()
    {
        if (is_object($this->children)) {
            $this->children->setInitialized(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getName() ?: '-';
    }

    /**
     * Converts the headers passed as string to an array
     *
     * @param string $rawHeaders The headers
     *
     * @return array
     */
    protected function getHeadersAsArray($rawHeaders)
    {
        $headers = array();

        foreach (explode("\r\n", $rawHeaders) as $header) {
            if (false != strpos($header, ':')) {
                list($name, $headerStr) = explode(':', $header, 2);
                $headers[trim($name)] = trim($headerStr);
            }
        }

        return $headers;
    }

    /**
     * Converts the headers passed as an associative array to a string
     *
     * @param array $headers The headers
     *
     * @return string
     */
    protected function getHeadersAsString(array $headers)
    {
        $rawHeaders = array();

        foreach ($headers as $name => $header) {
            $rawHeaders[] = sprintf('%s: %s', trim($name), trim($header));
        }

        $rawHeaders = implode("\r\n", $rawHeaders);

        return $rawHeaders;
    }

    /**
     * @return mixed
     */
    public static function getSlugifyMethod()
    {
        return self::$slugifyMethod;
    }

    /**
     * @param mixed $slugifyMethod
     */
    public static function setSlugifyMethod(\Closure $slugifyMethod)
    {
        self::$slugifyMethod = $slugifyMethod;
    }

    /**
     * Returns page name containing parent page name
     * @return string
     */
    public function getLongName() {
        $name = $this->getName();

        foreach ($this->getParents() as $parent) {
            $name = $parent->getName() . '/' . $name;
        }

        return $name;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSnapshots()
    {
        return $this->snapshots;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $snapshots
     * @return $this
     */
    public function setSnapshots($snapshots)
    {
        $this->snapshots = $snapshots;
        return $this;
    }
}
