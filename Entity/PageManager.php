<?php

namespace Symbio\OrangeGate\PageBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Sonata\PageBundle\Entity\PageManager as BaseEntityManager;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\Page as ModelPage;
use Sonata\DatagridBundle\Pager\Doctrine\Pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;

/**
 * This class manages PageInterface persistency with the Doctrine ORM
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class PageManager extends BaseEntityManager implements PageManagerInterface
{
    /**
     * @param \Sonata\PageBundle\Model\PageInterface $page
     *
     * @return void
     */
    public function fixUrl(PageInterface $page)
    {
        if ($page->isInternal()) {
            $page->setUrl(null); // internal routes do not have any url ...

            return;
        }

        // hybrid page cannot be altered
        if (!$page->isHybrid()) {

            // make sure Page has a valid url
            if ($page->getParent()) {
                foreach ($page->getTranslations() as $trans) {
                    $locale = $trans->getLocale();

                    if (!$trans->getSlug()) {
                        $trans->setSlug(ModelPage::slugify($trans->getName()));
                    }

                    $parent = $page->getParent();
                    foreach ($parent->getTranslations() as $ptrans) {
                        if ($ptrans->getLocale() === $locale) {
                            $url = $ptrans->getUrl();
                            if ($url == '/') {
                                $base = '/';
                            } elseif (substr($url, -1) != '/') {
                                $base = $url.'/';
                            } else {
                                $base = $url;
                            }

                            $trans->setUrl($base.$trans->getSlug()) ;
                        }
                    }
                }
            } else {
                foreach ($page->getTranslations() as $trans) {
                    // a parent page does not have any slug - can have a custom url ...
                    $trans->setSlug(null);
                    $trans->setUrl('/'.$trans->getSlug());
                }
            }
        }

        foreach ($page->getChildren() as $child) {
            $this->fixUrl($child);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save($page, $andFlush = true)
    {
        if (!$page->isHybrid()) {
            $this->fixUrl($page);
        }

        parent::save($page, $andFlush);

        return $page;
    }

    /**
     * {@inheritdoc}
     */
    public function loadPages(SiteInterface $site)
    {
        $query = $this->getEntityManager()
            ->createQuery(sprintf('SELECT p FROM %s p INDEX BY p.id WHERE p.site = %d ORDER BY p.position ASC', $this->class, $site->getId()));

        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );

        $query->setHint(
            \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $site->getLanguageVersions()[0]->getLocale()
        );

        $pages = $query->execute();

        foreach ($pages as $page) {
            $parent = $page->getParent();

            $page->disableChildrenLazyLoading();
            if (!$parent) {
                continue;
            }

            $pages[$parent->getId()]->disableChildrenLazyLoading();
            $pages[$parent->getId()]->addChildren($page);
        }

        return $pages;
    }

    public function findOneByUrl($site, $url)
    {
        $query = $this->getEntityManager()->createQuery(sprintf('
            SELECT p
            FROM %s p
            WHERE p.url = :url
            AND p.site = :site
        ', $this->class))->setParameters(array(
            'url' => $url,
            'site' => $site,
        ));

        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $query->setHint(
            \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $site->getLocale()
        );

        return $query->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getHybridPages(SiteInterface $site)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from( $this->class, 'p')
            ->where('p.routeName <> :routeName and p.site = :site')
            ->setParameters(array(
                'routeName' => PageInterface::PAGE_ROUTE_CMS_NAME,
                'site' => $site->getId()
            ))
            ->getQuery()
            ->execute();
    }
}
