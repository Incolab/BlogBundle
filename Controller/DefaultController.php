<?php

namespace Incolab\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;

use Incolab\BlogBundle\Entity\Comment;
use Incolab\BlogBundle\Form\Type\CommentType;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        return $this->forward('IncolabBlogBundle:Default:showPage', array('page' => 1));
    }
    
    public function showPageAction($page)
    {
        if ($page < 1) {
            throw $this->createNotFoundException('Une page ne peux pas être inférieur à 1');
        }
        $limit = $this->container->getParameter('incolab_blog.news.news_by_page');
        
        $offset = ($page -1) * $limit;
        // getIndex(@limit, @offset, @nbCaracters)
        $news = $this->getDoctrine()->getRepository('IncolabBlogBundle:News')->getIndex($limit, $offset, $this->container->getParameter('incolab_blog.news.nb_char_index'));
        
        if ($news === NULL) {
            throw $this->createNotFoundException('Cette page n\'existe pas');
        }
        
        $pageParam = array('prev' => false,
                           'next' => false,
                           'prevPage' => 0,
                           'nextPage' => 0
                           );
        
        $debut = $page;
        $numNews = $this->getDoctrine()->getRepository('IncolabBlogBundle:News')->getTotalPublishedNumber();
        
        if (($debut) > 1) {
            $pageParam['prevPage'] = $debut - 1;
            $pageParam['prev'] = true;
        }
        
        
        $nextPage = $debut + 1;
        
        if (($page * $limit)  < $numNews) {
            $pageParam['nextPage'] = $nextPage;
            $pageParam['next'] = true;
        }
        
        
        return $this->render('IncolabBlogBundle:Default:index.html.twig', array('news' => $news, 'pageParam' => $pageParam));
        
    }
    
    public function showNewsAction($slug)
    {
        $news = $this->getDoctrine()->getRepository('IncolabBlogBundle:News')->getOneAndCommentsBySlugByCreatedAtASC($slug);
        
        
        if ($news === NULL) {
            throw $this->createNotFoundException('News not found.');
        }
        
        $formComment = $this->createForm(CommentType::class, new Comment());
        
        return $this->render('IncolabBlogBundle:News:show.html.twig', array('comment' => array(), 'news' => $news, 'formComment' => $formComment->createView()));
    }
    
    public function createCommentAction($slug, Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }
        
        $news = $this->getDoctrine()->getRepository('IncolabBlogBundle:News')->getOneAndCommentsBySlugByCreatedAtASC($slug);
        
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
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();
            
            $this->addFlash('success', 'Comment added');
            
            return $this->redirectToRoute('blog_news_show', array('slug' => $news->getSlug()));
        } else {
            return $this->render('IncolabBlogBundle:News:show.html.twig', array('comment' => $comment, 'news' => $news, 'formComment' => $formComment->createView()));
        }
        
    }
}
