<?php

namespace Incolab\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Incolab\BlogBundle\Entity\News;
use Incolab\BlogBundle\Form\Type\NewsType;

class AdminController extends Controller {

    public function indexAction() {
        $news = $this->get("db")->getRepository("IncolabBlogBundle:News")->getByCreatedAtDESC();

        $lastsComments = $this->get("db")->getRepository("IncolabBlogBundle:Comment")->getLasts(10);

        return $this->render('IncolabBlogBundle:Admin:index.html.twig', array('news' => $news, 'lastsComments' => $lastsComments));
    }

    public function newsCreateAction(Request $request) {
        $form = $this->createForm(NewsType::class, new News());

        $form->handleRequest($request);

        return $this->render('IncolabBlogBundle:Admin:AddNews.html.twig', array('newsForm' => $form->createView()));
    }

    public function newsAddAction(Request $request) {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setAuthor($this->getUser());
            $news->setSlug($this->container->get('incolab_core.transliterator')->urlize($news->getTitle()));
            $news->setCreatedAt(new \DateTime());
            $contentparsed = $news->getContent();
            $this->get("db")->getRepository("IncolabBlogBundle:News")->persist($news);

            $this->addFlash('success', 'La news a été ajoutée');
            return $this->redirectToRoute('blog_admin_homepage');
        }

        return $this->render('IncolabBlogBundle:Admin:AddNews.html.twig', array('newsForm' => $form->createView()));
    }

    public function newsEditAction($slug, Request $request) {
        $newsRepository = $this->get("db")->getRepository('IncolabBlogBundle:News');
        $news = $newsRepository->findOneAndCommBySlug($slug, false);

        if ($news === NULL) {
            throw $this->createNotFoundException('News not found.');
        }

        $form = $this->createForm(NewsType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $news->setUpdatedAt(new \DateTime());
            $newsRepository->persist($news);

            $this->addFlash('success', 'News updated!');
            return $this->redirectToRoute('blog_admin_homepage');
        }

        return $this->render('IncolabBlogBundle:Admin:EditNews.html.twig', array('newsForm' => $form->createView()));
    }

    public function newsDeleteAction($slug, Request $request) {
        $newsRepository = $this->get("db")->getRepository('IncolabBlogBundle:News');
        $news = $newsRepository->findOneAndCommBySlug($slug);

        if ($news === NULL) {
            throw $this->createNotFoundException('News not found.');
        }
        
        $newsRepository->remove($news);

        $this->addFlash('success', 'News Deleted!');
        return $this->redirectToRoute('blog_admin_homepage');
    }

    public function commentDeleteAction($slugNews, $commentId) {
        $commentRepo = $this->get('db')->getRepository('IncolabBlogBundle:Comment');
        $comment = $commentRepo->getOneBySlugNewsAndCommentId($slugNews, $commentId);

        if ($comment === NULL) {
            throw $this->createNotFoundException('Ce commentaire n\'existe pas');
        }
        
        $commentRepo->remove($comment);

        $this->addFlash('success', 'Comment deleted.');
        return $this->redirectToRoute('blog_admin_homepage');
    }

}
