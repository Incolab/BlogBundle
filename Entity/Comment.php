<?php

namespace Incolab\BlogBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Comment
 */
class Comment
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $author;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 10,
     *      minMessage = "Ce champ doit contenir {{ limit }} caractères minimum"
     * )
     */
    private $content;

    /**
     * @var int
     */
    private $news;

    /**
     * @var \DateTime
     */
    private $createdAt;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = null;
    }
    
    /**
     * 
     * @param int $id
     * @return Comment
     */
    public function setId($id)
    {
        $this->id = $id;
        
        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Comment
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Comment
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set author
     *
     * @param \UserBundle\Entity\User $author
     *
     * @return Comment
     */
    public function setAuthor(\UserBundle\Security\User\User $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return \UserBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set news
     *
     * @param \Incolab\BlogBundle\Entity\News $news
     *
     * @return Comment
     */
    public function setNews(\Incolab\BlogBundle\Entity\News $news = null)
    {
        $this->news = $news;

        return $this;
    }

    /**
     * Get news
     *
     * @return \Incolab\BlogBundle\Entity\News
     */
    public function getNews()
    {
        return $this->news;
    }
}
