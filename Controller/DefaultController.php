<?php

namespace Incolab\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Incolab\BlogBundle\Service\CommentService;
use Incolab\BlogBundle\Entity\Comment;
use Incolab\BlogBundle\Form\Type\CommentType;

class DefaultController extends Controller
{

    public function indexAction(Request $request)
    {
        //return $this->forward('IncolabBlogBundle:Default:showPage', ['page' => 1]);
        return $this->showPageAction(1);
    }

    public function showPageAction($page)
    {
        if ($page < 1) {
            throw $this->createNotFoundException('Une page ne peux pas être inférieur à 1');
        }
        $limit = $this->container->getParameter('incolab_blog.news.news_by_page');

        $offset = ($page - 1) * $limit;
        // getIndex(@limit, @offset, @nbCaracters)
        $newsRepository = $this->get('db')->getRepository("IncolabBlogBundle:News");
        $news = $newsRepository->getIndex($limit, $offset);

        if (empty($news)) {
            throw $this->createNotFoundException('Cette page n\'existe pas');
        }

        $numNews = $newsRepository->getTotalPublishedNumber();
        $pageParam = $this->get("incolab_core.pagination")
                ->getSimplePagination($numNews, $page, $limit);

        return $this->render('IncolabBlogBundle:Default:index.html.twig', ['news' => $news, 'pageParam' => $pageParam]);
    }

    public function showNewsAction($slug)
    {
        $news = $this->get('db')
                        ->getRepository("IncolabBlogBundle:News")->findOneAndCommBySlug($slug);

        if ($news === NULL) {
            throw $this->createNotFoundException('News not found.');
        }

        $formComment = $this->createForm(CommentType::class, new Comment());

        return $this->render('IncolabBlogBundle:News:show.html.twig', ['news' => $news, 'form' => $formComment->createView()]);
    }

    public function createCommentAction($slug)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        $commentService = $this->get("blog.comment");

        $res = $commentService->addComment($slug, $this->getUser());
        $renderArgs = $commentService->getRenderArgs();

        switch ($res) {
            case CommentService::NEWS_NOT_FOUND:
                throw $this->createNotFoundException('News not found.');

            case CommentService::COMMENT_ADDED:
                $this->addFlash('success', 'Comment added');
                return $this->redirectToRoute('blog_news_show', ['slug' => $renderArgs["slug"]]);

            case CommentService::FORM_INVALID:
                return $this->render('IncolabBlogBundle:News:show.html.twig', $renderArgs);

            case CommentService::EVT_INVALID:
                $this->addFlash('error', $renderArgs["error"]);
                return $this->render('IncolabBlogBundle:News:show.html.twig', $renderArgs);
        }
    }

}
