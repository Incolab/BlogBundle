<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Incolab\BlogBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Incolab\BlogBundle\Entity\Comment;

/**
 * Description of MessageEvent
 *
 * @author david
 */
class CommentEvent extends Event {

    const VALID = 0;
    const NOT_VALID = 1;

    protected $comment;
    protected $status;
    protected $messageStatus;

    public function __construct(Comment $comment) {
        $this->comment = $comment;
        $this->status = self::VALID;
        $this->messageStatus = "";
    }

    // Le listener doit avoir accès au message
    public function getComment(): Comment {
        return $this->comment;
    }

    // Le listener doit pouvoir modifier le message
    public function setComment(Comment $comment): CommentEvent {
        $this->comment = $comment;
        return $this;
    }

    public function setStatus(int $status): CommentEvent {
        $this->status = $status;
        return $this;
    }

    public function isValid(): bool {
        if ($this->status === self::VALID) {
            return true;
        }

        return false;
    }
    
    public function setMessageStatus(string $msg): CommentEvent {
        $this->messageStatus = $msg;
        return $this;
    }
    
    // Le listener doit avoir accès au message
    public function getMessageStatus(): string {
        return $this->messageStatus;
    }

}
