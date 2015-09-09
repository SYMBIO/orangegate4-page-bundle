<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Sonata\PageBundle\Model\Site as BaseSite;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="page__site")
 * @ORM\HasLifecycleCallbacks()
 */
class Site extends BaseSite
{

    /**
     * @var integer $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="LanguageVersion", mappedBy="site", cascade={"persist", "remove"})
     */
    protected $languageVersions;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @var DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var DateTime
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $host;

    /**
     * @var string
     * @ORM\Column(name="relative_path", type="string", length=255, nullable=true)
     */
    protected $relativePath;

    /**
     * @var DateTime
     * @ORM\Column(name="enabled_from", type="datetime", nullable=true)
     */
    protected $enabledFrom;

    /**
     * @var DateTime
     * @ORM\Column(name="enabled_to", type="datetime", nullable=true)
     */
    protected $enabledTo;

    /**
     * @var boolean
     * @ORM\Column(name="is_default", type="boolean")
     */
    protected $isDefault;

    protected $formats = array();

    /**
     * @var string
     * @ORM\Column(name="locale", type="string", length=6, nullable=true)
     */
    protected $locale;

    /**
     * @var string
     * @ORM\Column(name="title", type="string", length=64, nullable=true)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="meta_keywords", type="string", length=255, nullable=true)
     */
    protected $metaKeywords;

    /**
     * @var string
     * @ORM\Column(name="meta_description", type="string", length=255, nullable=true)
     */
    protected $metaDescription;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="Symbio\OrangeGate\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="favicon", referencedColumnName="id", nullable=true)
     */
    protected $favicon;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    protected $slug;

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        $now = new \DateTime;

        if ($this->getEnabledFrom() instanceof \DateTime && $this->getEnabledFrom()->format('U') > $now->format('U')) {
            return false;
        }

        if ($this->getEnabledTo() instanceof \DateTime && $now->format('U') > $this->getEnabledTo()->format('U')) {
            return false;
        }

        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        if ($this->isLocalhost()) {
            return $this->getRelativePath();
        }

        return sprintf('http://%s%s', $this->getHost(), $this->getRelativePath());
    }

    /**
     * @return bool
     */
    public function isLocalhost()
    {
        return $this->getHost() === 'localhost';
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getName() ? : 'n/a';
    }

    /**
     * {@inheritdoc}
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormats($formats)
    {
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
        $slugify = new \Cocur\Slugify\Slugify();
        $this->setSlug($slugify->slugify($name));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelativePath($relativePath)
    {
        $this->relativePath = $relativePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * {@inheritdoc}
     */
    public function setIsDefault($default)
    {
        $this->isDefault = $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabledFrom(\DateTime $enabledFrom = null)
    {
        $this->enabledFrom = $enabledFrom;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabledFrom()
    {
        return $this->enabledFrom;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabledTo(\DateTime $enabledTo = null)
    {
        $this->enabledTo = $enabledTo;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabledTo()
    {
        return $this->enabledTo;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetaKeywords($metaKeywords)
    {
        $this->metaKeywords = $metaKeywords;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
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
     * Constructor
     */
    public function __construct()
    {
        $this->enabled = true;
        $this->isDefault = false;
        $this->languageVersions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add languageVersions
     *
     * @param LanguageVersion $languageVersions
     * @return Site
     */
    public function addLanguageVersion(LanguageVersion $languageVersions)
    {
        $this->languageVersions[] = $languageVersions;

        return $this;
    }

    /**
     * Remove languageVersions
     *
     * @param LanguageVersion $languageVersions
     */
    public function removeLanguageVersion(LanguageVersion $languageVersions)
    {
        $this->languageVersions->removeElement($languageVersions);
    }

    /**
     * Get languageVersions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLanguageVersions()
    {
        return $this->languageVersions;
    }

    public function getLanguageVersion($locale)
    {
        foreach ($this->languageVersions as $lv) {
            if ($lv->getLocale() === $locale) {
                return $lv;
            }
        }

        return null;
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
     * @return array
     */
    public function getLocales()
    {
        $locales = array();
        foreach ($this->getLanguageVersions() as $lv) {
            /**
             * @var LanguageVersion $lv
             */
            $locales[] = $lv->getLocale();
        }
        return $locales;
    }

    public function getFavicon()
    {
        return $this->favicon;
    }

    public  function setFavicon($favicon)
    {
        $this->favicon = $favicon;
        return $this;
    }

    /**
     * Get slug
     *
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set slug
     *
     * @param mixed $slug
     * @return Site
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }


}
