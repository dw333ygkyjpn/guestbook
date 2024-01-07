<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class AdminController extends AbstractController
{
    #[Route('/admin/review/comment/{id}', name: 'review_comment')]
    public function index(Request $request, Comment $comment, WorkflowInterface $commentAuditStateMachine,
                          EntityManagerInterface $manager): Response
    {
        $accepted = !$request->get('reject');
        $canPublish = $commentAuditStateMachine->can($comment, 'publish');

        if(!$canPublish){
            return new Response('Comment cannot be published', Response::HTTP_NOT_ACCEPTABLE);
        }

        $transition = $accepted ? 'publish' : 'reject';

        $commentAuditStateMachine->apply($comment, $transition);

        $manager->persist($comment);
        $manager->flush();

        return $this->render('admin/review.html.twig',[
            'comment' => $comment,
            'transition' => $transition
        ]);
    }
}
