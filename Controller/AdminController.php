<?php

namespace Incolab\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;

use Incolab\BlogBundle\Entity\News;
use Incolab\BlogBundle\Form\Type\NewsType;

class AdminController extends Controller
{
    public function indexAction()
    {        
        $news = $this->getDoctrine()->getRepository('IncolabBlogBundle:News')->getByCreatedAtDESC();
        $lastsComments = $this->getDoctrine()->getRepository('IncolabBlogBundle:Comment')->getLasts(5);
        
        return $this->render('IncolabBlogBundle:Admin:index.html.twig', array('news' => $news, 'lastsComments' => $lastsComments));
    }
    
    public function newsCreateAction(Request $request)
    {
        $form = $this->createForm(NewsType::class, new News());
        
        $form->handleRequest($request);
        
        return $this->render('IncolabBlogBundle:Admin:AddNews.html.twig', array('newsForm' => $form->createView()));
    }
    
    public function newsAddAction(Request $request)
    {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $news->setAuthor($this->getUser());
            $news->setSlug($this->container->get('core.transliterator')->urlize($news->getTitle()));
            $news->setCreatedAt(new \DateTime());
            $contentparsed = $news->getContent();
            $em->persist($news);
            $em->flush();
            
            $this->addFlash('success', 'La news a été ajoutée');
            return $this->redirectToRoute('blog_admin_homepage');
        } else {
            return $this->render('IncolabBlogBundle:Admin:AddNews.html.twig', array('newsForm' => $form->createView()));
        }
    }
    
    public function newsEditAction($slug, Request $request)
    {
        $news = $this->getDoctrine()->getRepository('IncolabBlogBundle:News')->findOneBySlug($slug);
        //$news = new News();
        
        if ($news === NULL) {
            throw $this->createNotFoundException('News not found.');
        }
        
        $form = $this->createForm(NewsType::class, $news);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $news->setUpdatedAt(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();
            
            $this->addFlash('success', 'News updated!');
            return $this->redirectToRoute('blog_admin_homepage');
        } else {
            return $this->render('IncolabBlogBundle:Admin:EditNews.html.twig', array('newsForm' => $form->createView()));
        }
    }
    
    public function newsDeleteAction($slug, Request $request)
    {
        $news = $this->getDoctrine()->getRepository('IncolabBlogBundle:News')->findOneBySlug($slug);
        
        if ($news === NULL) {
            throw $this->createNotFoundException('News not found.');
        } else {
            $em = $this->getDoctrine()->getManager();
            $em->remove($news);
            $em->flush();
            
            $this->addFlash('success', 'News Deleted!');
            return $this->redirectToRoute('blog_admin_homepage');
        }
        
    }
    
    public function commentDeleteAction($slugNews, $commentId)
    {
        $comment = $this->getDoctrine()->getRepository('IncolabBlogBundle:Comment')->getOneBySlugNewsAndCommentId($slugNews, $commentId);
        
        if ($comment === NULL) {
            throw $this->createNotFoundException('Ce commentaire n\'existe pas');
        } else {
            $em = $this->getDoctrine()->getManager();
            $em->remove($comment);
            $em->flush();
            
            $this->addFlash('success', 'Comment deleted.');
            return $this->redirectToRoute('blog_admin_homepage');
        }
        
    }
}