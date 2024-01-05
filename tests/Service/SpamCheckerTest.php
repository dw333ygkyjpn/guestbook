<?php

namespace App\Tests\Service;

use App\Entity\Comment;
use App\Service\SpamChecker;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SpamCheckerTest extends TestCase
{
    public function testClassWithInvalidRequest(): void
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();

        $context = [];

        $mockClient = new MockHttpClient([
            new MockResponse('invalid', [
                'response_headers' => ['X-akismet-debug-help: Invalid Key']
            ])
        ]);

        $checker = new SpamChecker($mockClient, 'invalidkey', 'prod');
        $this->expectException(\RuntimeException::class);
        $checker->getSpamScore($comment, $context);
    }

    /**
     * @dataProvider provideComments
     */
    public function testClassWithRequests(int $expectedScore, MockResponse $response
        , Comment $comment, array $context)
    {
        $client = new MockHttpClient([$response]);
        $checker = new SpamChecker($client, 'whatever', 'prod');

        $score = $checker->getSpamScore($comment, $context);
        $this->assertSame($expectedScore, $score);
    }

    public static function provideComments(): iterable{
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $response = new MockResponse('false');
        yield 'good' => [0, $response, $comment, $context];

        $response = new MockResponse('true');
        yield 'suspicious' => [1, $response, $comment, $context];

        $response = new MockResponse('true', [
           'response_headers' => ['X-akismet-pro-tip: discard']
        ]);
        yield 'spam' => [2, $response, $comment, $context];
    }
}
