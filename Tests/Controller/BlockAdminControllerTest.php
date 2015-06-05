<?php

namespace Symbio\OrangeGate\PageBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;

class BlockAdminControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function testIndex()
    {
        $this->logIn();

        // nejdriv vytvorime blok
        $crawler = $this->client->request('GET', '/cs/admin/orangegate/page/page/475/block/create?type=orangegate.admin.block.formatter');
        $buttonCrawlerNode = $crawler->selectButton('submit');
        var_dump($crawler);exit;
        $form = $buttonCrawlerNode->form(array(
            'parent' => 596,
            'translations[cs][enabled]' => true,
            'translations[cs][settings][content][rawContent]' => 'a',
        ));
        $crawler = $client->submit($form);

        // pak se automaticky vratime do editace a zkusime zmenit data
        $buttonCrawlerNode = $crawler->selectButton('submit');
        $form = $buttonCrawlerNode->form(array(
            'translations[cs][settings][content][rawContent]'  => 'b',
        ));
        $crawler = $client->submit($form);

        $buttonCrawlerNode = $crawler->selectButton('submit');
        $form = $buttonCrawlerNode->form();

        var_dump($form->getData());
        exit;
    }

    private function logIn()
    {
        $session = $this->client->getContainer()->get('session');

        $firewall = 'admin';
        $token = new UsernamePasswordToken('admin', null, $firewall, array('ROLE_SUPER_ADMIN'));
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
