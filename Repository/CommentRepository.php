<?php

namespace Incolab\BlogBundle\Repository;

use Incolab\DBALBundle\Manager\Manager;
use UserBundle\Security\User\User;
use Incolab\BlogBundle\Entity\Comment;
use UserBundle\Repository\UserRepository;
use Incolab\BlogBundle\Repository\NewsRepository;

/**
 * CommentRepository
 */
class CommentRepository extends Manager {
    /*
     * id        | integer                        | non NULL
     * author_id | integer                        | non NULL
     * news_id   | integer                        | non NULL
     * content   | text                           | non NULL
     * createdat | timestamp(0) without time zone | non NULL
     * Index :
     * "blog_comment_pkey" PRIMARY KEY, btree (id)
     * "idx_7882efefb5a459a0" btree (news_id)
     * "idx_7882efeff675f31b" btree (author_id)
     * Contraintes de clés étrangères :
     * "fk_7882efefb5a459a0" FOREIGN KEY (news_id) REFERENCES blog_news(id)
     * "fk_7882efeff675f31b" FOREIGN KEY (author_id) REFERENCES user_account(id)
     */

    const SQL_GETLASTS = "SELECT c.id AS c_id, c.author_id AS c_author_id, c.content AS c_content, c.createdat AS c_createdat,"
            . "ac.id AS ac_id, ac.username AS ac_username, "
            . "n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished "
            . "FROM blog_comment c "
            . "LEFT JOIN user_account ac ON c.author_id = ac.id LEFT JOIN blog_news n ON c.news_id = n.id "
            . "ORDER BY c.createdat DESC LIMIT ?";
    const SQL_INSERT = "INSERT INTO blog_comment (id, author_id, news_id, content, createdat) "
            . "VALUES (nextval('blog_comment_id_seq'),?,?,?,?)";
    const SQL_UPDATE = "UPDATE blog_comment SET author_id = ?, news_id = ?, content = ?, createdat = ? "
            . "WHERE id = ?";

    public static function hydrate($data = [], $key = "") {
        $comm = new Comment();
        $comm->setId($data[$key . "_id"])
                ->setAuthor(UserRepository::lightHydrateUser($data, "a" . $key))
                ->setContent($data[$key . "_content"])
                ->setCreatedAt(\DateTime::createFromFormat("Y-m-d H:i:s", $data[$key . "_createdat"]));

        return $comm;
    }

    public function getOneBySlugNewsAndCommentId($slugNews, $commentId) {
        $sql = "SELECT c.id AS c_id, c.author_id AS c_author_id, c.content AS c_content, c.createdat AS c_createdat, "
                . "ac.id AS ac_id, ac.username AS ac_username, "
                . "n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
                . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished "
                . "FROM blog_comment c "
                . "LEFT JOIN user_account ac ON c.author_id = ac.id LEFT JOIN blog_news n ON c.news_id = n.id "
                . "WHERE c.id = ? AND n.slug = ?";

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

    public function getLasts($nbComments) {
        $stmt = $this->dbal->prepare(self::SQL_GETLASTS);
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

    public function persist(Comment $comment) {
        if ($comment->getId() === null) {
            return $this->insert($comment);
        }

        return $this->update($comment);
    }

    private function insert(Comment $comment) {
        $stmt = $this->dbal->prepare(self::SQL_INSERT);
        $stmt->bindValue(1, $comment->getAuthor()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $comment->getNews()->getId(), \PDO::PARAM_STR);
        $stmt->bindValue(3, $comment->getContent(), \PDO::PARAM_STR);
        $stmt->bindValue(4, $comment->getCreatedAt()->format("Y-m-d H:i:s"), \PDO::PARAM_STR);
        $stmt->execute();

        $comment->setId($this->dbal->lastInsertId());

        $stmt->closeCursor();

        return $comment;
    }

    private function update(Comment $comment) {
        $stmt = $this->dbal->prepare(self::SQL_INSERT);
        $stmt->bindValue(1, $comment->getAuthor()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $comment->getNews()->getId(), \PDO::PARAM_STR);
        $stmt->bindValue(3, $comment->getContent(), \PDO::PARAM_STR);
        $stmt->bindValue(4, $comment->getCreatedAt()->format("Y-m-d H:i:s"), \PDO::PARAM_STR);
        $stmt->bindValue(5, $comment->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        $stmt->closeCursor();

        return $comment;
    }

    public function remove(Comment $comment) {
        $sql = "DELETE FROM blog_comment WHERE id = ?";
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $comment->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
    }

    public function create_database() {
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
