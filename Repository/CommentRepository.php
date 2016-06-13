<?php

namespace Incolab\BlogBundle\Repository;

/**
 * CommentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CommentRepository extends \Doctrine\ORM\EntityRepository
{
    public function getOneBySlugNewsAndCommentId($slugNews, $commentId)
    {
        $comment = $this->createQueryBuilder('c')
            ->leftJoin('c.news', 'n')
            ->where('n.slug = :slugNews AND c.id = :commentId')
            ->setParameters(array(':slugNews' => $slugNews, ':commentId' => $commentId))
            ->getQuery()->getOneOrNullResult();
        
        return $comment;
    }
    
    public function getLasts($nbComments)
    {
        $comment = $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()->setMaxResults($nbComments)->getResult();
        
        return $comment;
    }
}
