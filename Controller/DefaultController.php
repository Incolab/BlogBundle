<?php

namespace Incolab\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Incolab\BlogBundle\Entity\Comment;
use Incolab\BlogBundle\Form\Type\CommentType;
use Incolab\BlogBundle\IncolabBlogEvents;
use Incolab\BlogBundle\Event\CommentEvent;

class DefaultController extends Controller {

    public function indexAction(Request $request) {
        //return $this->forward('IncolabBlogBundle:Default:showPage', ['page' => 1]);
        return $this->showPageAction(1);
    }

    public function showPageAction($page) {
        if ($page < 1) {
            throw $this->createNotFoundException('Une page ne peux pas être inférieur à 1');
        }
        $limit = $this->container->getParameter('incolab_blog.news.news_by_page');

        $offset = ($page - 1) * $limit;
        // getIndex(@limit, @offset, @nbCaracters)
        $newsRepository = $this->get('db')->getRepository("IncolabBlogBundle:News");
        $news = $newsRepository->getIndex($limit, $offset, $this->container->getParameter('incolab_blog.news.nb_char_index'));

        if (empty($news)) {
            throw $this->createNotFoundException('Cette page n\'existe pas');
        }

        $pageParam = [
            'prev' => false,
            'next' => false,
            'prevPage' => 0,
            'nextPage' => 0
        ];

        $debut = $page;
        $numNews = $newsRepository->getTotalPublishedNumber();
        if (($debut) > 1) {
            $pageParam['prevPage'] = $debut - 1;
            $pageParam['prev'] = true;
        }

        $nextPage = $debut + 1;

        if (($page * $limit) < $numNews) {
            $pageParam['nextPage'] = $nextPage;
            $pageParam['next'] = true;
        }

        return $this->render('IncolabBlogBundle:Default:index.html.twig', ['news' => $news, 'pageParam' => $pageParam]);
    }

    public function showNewsAction($slug) {
        $news = $this->get('db')
                        ->getRepository("IncolabBlogBundle:News")->findOneAndCommBySlug($slug);

        if ($news === NULL) {
            throw $this->createNotFoundException('News not found.');
        }

        $formComment = $this->createForm(CommentType::class, new Comment());

        return $this->render('IncolabBlogBundle:News:show.html.twig', ['news' => $news, 'formComment' => $formComment->createView()]);
    }

    public function createCommentAction($slug, Request $request) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }
        $newsRepository = $this->get("db")->getRepository("IncolabBlogBundle:News");
        $news = $newsRepository->findOneAndCommBySlug($slug);

        if ($news === NULL) {
            throw $this->createNotFoundException('News not found.');
        }

        $comment = new Comment();

        $formComment = $this->createForm(CommentType::class, $comment);
        $formComment->handleRequest($request);

        if ($formComment->isSubmitted() && $formComment->isValid()) {
            $comment->setAuthor($this->getUser());
            $comment->setNews($news);
            $comment->setCreatedAt(new \DateTime());

            $event = new CommentEvent($comment);
            $this->get("event_dispatcher")->dispatch(IncolabBlogEvents::ON_COMMENT_POST, $event);

            if (!$event->isValid()) {
                $this->addFlash('error', $event->getMessageStatus());
                return $this->render('IncolabBlogBundle:News:show.html.twig', ['comment' => $comment, 'news' => $news, 'formComment' => $formComment->createView()]);
            }

            $commentRepository = $this->get('db')->getRepository("IncolabBlogBundle:Comment");
            $commentRepository->persist($comment);

            $this->addFlash('success', 'Comment added');

            return $this->redirectToRoute('blog_news_show', ['slug' => $news->getSlug()]);
        }

        return $this->render('IncolabBlogBundle:News:show.html.twig', ['comment' => $comment, 'news' => $news, 'formComment' => $formComment->createView()]);
    }

}
