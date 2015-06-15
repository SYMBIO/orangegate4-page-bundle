<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Sonata\PageBundle\Entity\BaseBlock as BaseBlock;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="page__bloc")
 */
class Block extends BaseBlock
{

    /**
     * @var integer $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $children;

    /**
     * @var \Symbio\OrangeGate\PageBundle\Entity\Block
     */
    protected $parent;

    /**
     * @var \Symbio\OrangeGate\PageBundle\Entity\Page
     */
    protected $page;

    /**
     * @var array
     * @Gedmo\Translatable
     */
    protected $settings;

    /**
     * @var boolean
     * @Gedmo\Translatable
     */
    protected $enabled = true;

    /**
     * @ORM\OneToMany(targetEntity="BlockTranslation", mappedBy="object", indexBy="locale", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid
     */
    protected $translations;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();

        $this->setPosition(1);
    }

    /**
     * {@inheritDoc}
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
     * Updates dates before creating/updating entity
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime;
        $this->updatedAt = new \DateTime;
    }

    /**
     * Updates dates before updating entity
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime;
    }

    /**
     * {@inheritDoc}
     */
    public function setChildren($children)
    {
        $this->children = new ArrayCollection;

        foreach ($children as $child) {
            $this->addChildren($child);
        }
    }

    /**
     * Add children
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Block $children
     * @return Block
     */
    public function addChild(BlockInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Block $children
     */
    public function removeChild(BlockInterface $children)
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
     * Set parent
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Block $parent
     * @return Block
     */
    public function setParent(BlockInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Symbio\OrangeGate\PageBundle\Entity\Block
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set page
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\Page $page
     * @return Block
     */
    public function setPage(PageInterface $page = null)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get page
     *
     * @return \Symbio\OrangeGate\PageBundle\Entity\Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Add translations
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\PageTranslation $translations
     * @return Block
     */
    public function addTranslation(BlockTranslation $translations)
    {
        $this->translations[$translations->getLocale()] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Symbio\OrangeGate\PageBundle\Entity\PageTranslation $translations
     */
    public function removeTranslation(BlockTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
