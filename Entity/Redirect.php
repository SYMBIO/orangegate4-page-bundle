<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Sonata\PageBundle\Model\PageBlockInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\Page as ModelPage;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="page__redirect",indexes={@ORM\Index(name="enabled_idx", columns={"enabled"})})
 */
class Redirect
{

    /**
     * @var integer $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="languageVersions")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $site;

    /**
     * @ORM\Column(name="source_url", type="string", length=500)
     */
    protected $sourceUrl;

    /**
     * @ORM\Column(name="destination_url", type="string", length=500)
     */
    protected $destinationUrl;

    /**
     * @ORM\Column(type="integer", length=255)
     */
    protected $type = 302;

    /**
     * @ORM\Column(type="integer")
     * @Gedmo\Sortable()
     */
    protected $position;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $enabled = true;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $note;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return Redirect
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get site
     *
     * @return mixed
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set site
     *
     * @param mixed $site
     * @return Redirect
     */
    public function setSite($site)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * Get sourceUrl
     *
     * @return mixed
     */
    public function getSourceUrl()
    {
        return $this->sourceUrl;
    }

    /**
     * Set sourceUrl
     *
     * @param mixed $sourceUrl
     * @return Redirect
     */
    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;
        return $this;
    }

    /**
     * Get destinationUrl
     *
     * @return mixed
     */
    public function getDestinationUrl()
    {
        return $this->destinationUrl;
    }

    /**
     * Set destinationUrl
     *
     * @param mixed $destinationUrl
     * @return Redirect
     */
    public function setDestinationUrl($destinationUrl)
    {
        $this->destinationUrl = $destinationUrl;
        return $this;
    }

    /**
     * Get type
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param mixed $type
     * @return Redirect
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get position
     *
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set position
     *
     * @param mixed $position
     * @return Redirect
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return Redirect
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Redirect
     */
    public function setNote($note)
    {
        $this->note = $note;
        return $this;
    }

    public function __toString()
    {
        return $this->getSourceUrl();
    }

}
