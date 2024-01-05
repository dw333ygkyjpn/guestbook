<?php

namespace App\Service;

use App\Entity\Comment;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker
{
    protected HttpClientInterface $akismetClient;
    protected string $akismetKey;
    protected string $envStr;

    public function __construct(HttpClientInterface $akismetClient,
                                #[Autowire('%env(AKISMET_KEY)%')] string $akismetKey,
                                #[Autowire('%env(APP_ENV)%')] string $envStr)
    {
        $this->akismetClient = $akismetClient;
        $this->akismetKey = $akismetKey;
        $this->envStr = $envStr;
    }

    /**
     * @param Comment $comment
     * @param array $context
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * Check if comment is spam with Akismet api
     * 0 = Not spam, 1 = maybe spam, 2 = spam alert
     */
    public function getSpamScore(Comment $comment, array $context): int{
        $request = $this->akismetClient->request('POST', 'comment-check', [
            'body' => array_merge($context, [
                'api_key' => $this->akismetKey,
                'comment_type' => 'comment',
                'comment_author' => $comment->getAuthor(),
                'comment_author_email' => $comment->getEmail(),
                'comment_content' => $comment->getText(),
                'is_test' => $this->envStr == 'dev',
            ])
        ]);

        $headers = $request->getHeaders();
        $body = $request->getContent();

        if('discard' === ($headers['X-akismet-pro-tip'][0] ?? '')){
            return 2;
        }

        if(isset($headers['x-akismet-debug-help'][0])){
            throw new \RuntimeException("Error checking for spam: ${$body}.".PHP_EOL
                ."Context: ${$headers['x-akismet-debug-help'][0]}");
        }

        return $body == 'true' ? 1 : 0;
    }
}