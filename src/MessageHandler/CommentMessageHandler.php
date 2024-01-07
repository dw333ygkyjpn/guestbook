<?php

namespace App\MessageHandler;

use App\Entity\Comment;
use App\Message\CommentMessage;
use App\Repository\AdminRepository;
use App\Repository\CommentRepository;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class CommentMessageHandler implements MessageHandlerInterface
{

    public function __construct(protected EntityManagerInterface $manager,
                                protected CommentRepository $commentRepository,
                                protected SpamChecker $checker,
                                protected WorkflowInterface $commentAuditStateMachine,
                                protected LoggerInterface $logger,
                                protected MessageBusInterface $messageBus,
                                protected MailerInterface $mailer,
                                protected AdminRepository $adminRepository){}

    public function __invoke(CommentMessage $message): void
    {
        $comment = $this->commentRepository->find($message->getId());
        if(empty($comment)){
            return;
        }

        $canAccept = $this->commentAuditStateMachine->can($comment, 'accept');
        $canAutoPublish = $this->commentAuditStateMachine->can($comment, 'publish_good');
        $canPublish = $this->commentAuditStateMachine->can($comment, 'publish');

        if($canAccept){
            $checker = $this->checker->getSpamScore($comment, $message->getContext());
            $transition = match ($checker){
                1 => 'flag_spam',
                2 => 'reject_spam',
                default => 'accept'
            };
            $this->commentAuditStateMachine->apply($comment, $transition);
            $this->manager->flush();

            $this->messageBus->dispatch($message);
        }elseif ($canAutoPublish){
            $this->commentAuditStateMachine->apply($comment, 'publish_good');
            $this->manager->flush();
        }elseif ($canPublish){
            $admin = $this->adminRepository->find(1);
            $this->sendAdminNotification($admin->getEmail(), $comment);
        }else{
            $this->logger->notice('Comment cannot be processed.', ['comment' => $comment]);
        }
    }

    private function sendAdminNotification(string $adminEmail, Comment $comment): void
    {
        $this->mailer->send((new NotificationEmail())
            ->subject("Posible spam comment")
            ->htmlTemplate('emails/comment_review.html.twig')
            ->from($adminEmail)
            ->to($adminEmail)
            ->context(['comment' => $comment])
        );
    }
}
