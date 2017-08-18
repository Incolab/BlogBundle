<?php

namespace Incolab\BlogBundle\Repository;

use Incolab\DBALBundle\Manager\Manager;
use Incolab\BlogBundle\Repository\CommentSQL;
use Incolab\BlogBundle\Entity\Comment;
use UserBundle\Repository\UserRepository;
use Incolab\BlogBundle\Repository\NewsRepository;

/**
 * CommentRepository
 */
class CommentRepository extends Manager
{

    public static function hydrate($data = [], $key = "")
    {
        $comm = new Comment();
        $comm->setId($data[$key . "_id"])
                ->setAuthor(UserRepository::lightHydrateUser($data, "a" . $key))
                ->setContent($data[$key . "_content"])
                ->setCreatedAt(\DateTime::createFromFormat("Y-m-d H:i:s", $data[$key . "_createdat"]));

        return $comm;
    }

    public function getOneBySlugNewsAndCommentId($slugNews, $commentId)
    {
        $sql = sprintf(CommentSQL::SQL_FINDALL, "WHERE c.id = ? AND n.slug = ?");
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $commentId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $slugNews, \PDO::PARAM_STR);
        $stmt->execute();
        $res = $stmt->fetch();
        $stmt->closeCursor();

        if ($res === null) {
            return false;
        }

        $comm = self::hydrate($res, "c");
        $comm->setNews(NewsRepository::hydrate($res, "n"));

        return $comm;
    }

    public function getLasts($nbComments)
    {
        $sql = sprintf(CommentSQL::SQL_FINDALL, "ORDER BY c.createdat DESC LIMIT ?");
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $nbComments, \PDO::PARAM_INT);
        $stmt->execute();

        $comments = [];
        while ($res = $stmt->fetch()) {
            $comment = self::hydrate($res, "c");
            $comment->setNews(NewsRepository::hydrate($res, "n"));
            $comments[] = $comment;
        }
        $stmt->closeCursor();

        return $comments;
    }

    public function persist(Comment $comment)
    {
        if ($comment->getId() === null) {
            return $this->insert($comment);
        }

        return $this->update($comment);
    }

    private function insert(Comment $comment)
    {
        $stmt = $this->dbal->prepare(CommentSQL::SQL_INSERT);
        $stmt->bindValue(1, $comment->getAuthor()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $comment->getNews()->getId(), \PDO::PARAM_STR);
        $stmt->bindValue(3, $comment->getContent(), \PDO::PARAM_STR);
        $stmt->bindValue(4, $comment->getCreatedAt()->format("Y-m-d H:i:s"), \PDO::PARAM_STR);
        $stmt->execute();

        $comment->setId($this->dbal->lastInsertId());

        $stmt->closeCursor();

        return $comment;
    }

    private function update(Comment $comment)
    {
        $stmt = $this->dbal->prepare(CommentSQL::SQL_UPDATE);
        $stmt->bindValue(1, $comment->getAuthor()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $comment->getNews()->getId(), \PDO::PARAM_STR);
        $stmt->bindValue(3, $comment->getContent(), \PDO::PARAM_STR);
        $stmt->bindValue(4, $comment->getCreatedAt()->format("Y-m-d H:i:s"), \PDO::PARAM_STR);
        $stmt->bindValue(5, $comment->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        $stmt->closeCursor();

        return $comment;
    }

    public function remove(Comment $comment)
    {
        $sql = "DELETE FROM blog_comment WHERE id = ?";
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $comment->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
    }

    public function create_database()
    {
        $schemaManager = $this->dbal->getSchemaManager();

        $fromSchema = $schemaManager->createSchema();

        $schema = clone $fromSchema;

        $table = $schema->createTable("blog_comment");
        $table->addColumn("id", "integer", ["unsigned" => true]);
        $table->addColumn("author_id", "integer", ["unsigned" => true]);
        $table->addColumn("news_id", "integer", ["unsigned" => true]);
        $table->addColumn("content", "text");
        $table->addColumn("createdat", "datetime");

        $table->setPrimaryKey(["id"]);
        $schema->createSequence("blog_comment_id_seq");

        $userTable = $schema->getTable("user_account");
        $table->addForeignKeyConstraint($userTable, ["author_id"], ["id"], ["onDelete" => "CASCADE"]);
        $newsTable = $schema->getTable("blog_news");
        $table->addForeignKeyConstraint($newsTable, ["news_id"], ["id"], ["onDelete" => "CASCADE"]);

        $sql = $fromSchema->getMigrateToSql($schema, $this->dbal->getDatabasePlatform());

        return $sql;
    }

}
