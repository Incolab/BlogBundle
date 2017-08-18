<?php

/**
 * Blog Comment Service File
 * 
 * @author David Salbei <david@incolab.fr>
 * @copyright 2017 Incolab
 * @licence https://opensource.org/licenses/MIT MIT
 * 
 */

namespace Incolab\BlogBundle\Service;

use Incolab\DBALBundle\Service\DBALService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Incolab\BlogBundle\Entity\News;
use Incolab\BlogBundle\Entity\Comment;
use Incolab\BlogBundle\Form\Type\CommentType;
use Incolab\BlogBundle\Event\CommentEvent;
use Incolab\BlogBundle\IncolabBlogEvents;
use UserBundle\Entity\User;

/**
 * Comment Service
 *
 * @author David Salbei <david@incolab.fr>
 * @copyright 2017 Incolab
 * @licence https://opensource.org/licenses/MIT MIT
 */
class CommentService
{

    const NEWS_NOT_FOUND = 1;
    const COMMENT_ADDED = 2;
    const FORM_INVALID = 3;
    const EVT_INVALID = 4;

    /**
     *
     * @var DBALService
     */
    private $dbal;

    /**
     *
     * @var EventDispatcherInterface
     */
    private $evtDispatcher;

    /**
     *
     * @var Request
     */
    private $request;

    /**
     *
     * @var News
     */
    private $news;

    /**
     *
     * @var FormFactory
     */
    private $formFactory;

    /**
     *
     * @var User
     */
    private $user;
    
    /**
     *
     * @var array
     */
    private $renderArgs;

    public function __construct(
            DBALService $dbal,
            EventDispatcherInterface $evt,
            RequestStack $requests,
            FormFactory $fFactory
    ) {
        $this->dbal = $dbal;
        $this->evtDispatcher = $evt;
        $this->request = $requests->getCurrentRequest();
        $this->formFactory = $fFactory;
        $this->renderArgs = [];
    }
    
    public function getRenderArgs(): array
    {
        return $this->renderArgs;
    }
    
    public function getNews()
    {
        return $this->news;
    }

    public function addComment(string $newSlug, User $user): int
    {
        $this->user = $user;
        $newsRepository = $this->dbal->getRepository("IncolabBlogBundle:News");
        $this->news = $newsRepository->findOneAndCommBySlug($newSlug);

        if ($this->news === null) {
            return self::NEWS_NOT_FOUND;
        }

        return $this->prepareComment();
    }

    private function prepareComment(): int
    {
        $comment = new Comment();

        $formComment = $this->formFactory->create(CommentType::class, $comment);
        $formComment->handleRequest($this->request);

        if ($formComment->isSubmitted() && $formComment->isValid()) {
            return $this->createComment($formComment);
        }

        $this->renderArgs["error"] = "form.error";
        $this->renderArgs["news"] = $this->news;
        $this->renderArgs["form"] = $formComment->createView();
        
        return self::FORM_INVALID;
    }

    private function createComment(FormInterface $fComment): int
    {
        $comment = $fComment->getData();
        $comment->setAuthor($this->user);
        $comment->setNews($this->news);
        $comment->setCreatedAt(new \DateTime());

        $event = new CommentEvent($comment);
        $this->evtDispatcher->dispatch(IncolabBlogEvents::ON_COMMENT_POST, $event);

        if (!$event->isValid()) {
            $this->renderArgs["error"] = $event->getMessageStatus();
            $this->renderArgs["comment"] = $comment;
            $this->renderArgs["news"] = $this->news;
            $this->renderArgs["form"] = $fComment->createView();
            return self::EVT_INVALID;
        }

        $commentRepository = $this->dbal->getRepository("IncolabBlogBundle:Comment");
        $commentRepository->persist($comment);
        
        $this->renderArgs["slug"] = $this->news->getSlug();
        return self::COMMENT_ADDED;
    }

}
