<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConferenceController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $entityManager,
                                protected SpamChecker $spamChecker,
                                protected UrlGeneratorInterface $urlGenerator)
    {
    }

    #[Route('/', name: 'homepage', methods: ["GET"])]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig',
            [
                'conferences' => $conferenceRepository->findAll(),
            ]
        );
    }

    #[Route('/conference/{slug}', name: 'conference_show', methods: ["GET", "POST"])]
    public function showConference(Request $request,
                                   Conference $conference,
                                   CommentRepository $commentRepository, #[Autowire('%photo_dir%')] string $photoDir): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $charset = $request->getCharsets();
            $charset = !empty($charset) ? $charset[0] : 'UTF-8';

            $context = [
                'blog' => $this->urlGenerator->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
                'blog_lang' => $request->getLocale(),
                'blog_charset' => $charset,
            ];

            $spamScore = $this->spamChecker->getSpamScore($comment, $context);

            if($spamScore == 2){
                throw new \RuntimeException('Spam detected.');
            }elseif ($spamScore == 1){
                //TODO: Report and flag posible spam.
            }

            $comment->setConference($conference);

            if($photo = $form['photo']->getData()){
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                $photo->move($photoDir, $filename);
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->redirectToRoute('conference_show', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPagination($conference, $offset);

        return $this->render('conference/show.html.twig',
            [
                'conference' => $conference,
                'comment_form' => $form,
                'comments' => $paginator,
                'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
                'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE)
            ]
        );
    }
}
