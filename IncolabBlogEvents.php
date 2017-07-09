<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Incolab\BlogBundle;

/**
 * Description of IncolabBlogEvents
 *
 * @author david
 */
final class IncolabBlogEvents {
    
    /**
     * The ON_COMMENT_POST event occurs when a comment is posted on a news.
     *
     * This event allows you to modify or not persist the comment.
     *
     * @Event("Incolab\BlogBundle\Event\CommentEvent")
     */
    const ON_COMMENT_POST = "incolab_blog.comment.post";
}
