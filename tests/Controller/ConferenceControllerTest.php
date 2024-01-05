<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceControllerTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback!');
    }

    public function testClickConference(): void
    {
        $conference = "[TEST] Santiago 2023";
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        //In test db there is 2 conferences,
        //the view should show the 2
        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink($conference);

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleContains($conference);
    }
    
    public function testCommentSubmission(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');
        $crawler = $client->request('GET',
            $router->generate(
                'conference_show',
                ['slug' => 'test-santiago-2023']
            )
        );

        $csrfToken = $crawler->filter('#comment__token')->attr('value');

        $client->submitForm('Submit', [
            'comment[author]' => 'Nicolas',
            'comment[text]' => 'Test',
            'comment[email]' => 'Test@test.cl',
            'comment[photo]' => dirname(__DIR__, 2).'/public/img/site_u.gif',
            'comment[_token]' => $csrfToken,
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }
}
