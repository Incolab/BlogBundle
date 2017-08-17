<?php

namespace Incolab\BlogBundle\Repository;

use Incolab\DBALBundle\Manager\Manager;
use UserBundle\Security\User\User;
use Incolab\BlogBundle\Entity\News;
use UserBundle\Repository\UserRepository;
use Incolab\BlogBundle\Repository\CommentRepository;

/**
 * NewsRepository
 */
class NewsRepository extends Manager {
    /*
     * Table name: blog_news
     * 
     *  id         | integer                        | non NULL                                                                                                                                                                                                                       
     * author_id   | integer                        |
     * title       | character varying(255)         | non NULL
     * slug        | character varying(255)         | non NULL
     * content     | text                           | non NULL
     * createdat   | timestamp(0) without time zone | non NULL
     * updatedat   | timestamp(0) without time zone | Par défaut, NULL::timestamp without time zone
     * ispublished | boolean                        | non NULL
     */

    const SQL_FINDWITHCOMM = "SELECT n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished, "
            . "an.id AS an_id, an.username AS an_username, "
            . "c.id AS c_id, c.createdat AS c_createdat, c.content AS c_content, "
            . "ac.id AS ac_id, ac.username AS ac_username "
            . "FROM blog_news n "
            . "LEFT JOIN user_account an ON n.author_id = an.id "
            . "LEFT JOIN blog_comment c ON n.id = c.news_id "
            . "LEFT JOIN user_account ac ON c.author_id = ac.id "
            . "%s";
    const SQL_INDEX = "SELECT n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished, "
            . "an.id AS an_id, an.username AS an_username "
            . "FROM blog_news n "
            . "LEFT JOIN user_account an ON n.author_id = an.id "
            . "WHERE n.ispublished = true "
            . "ORDER BY n.createdat DESC "
            . "LIMIT ? OFFSET ?";
    const SQL_ONEANDCOMMBYSLUG = "SELECT n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished, "
            . "an.id AS an_id, an.username AS an_username, "
            . "c.id AS c_id, c.createdat AS c_createdat, c.content AS c_content, "
            . "ac.id AS ac_id, ac.username AS ac_username "
            . "FROM blog_news n "
            . "LEFT JOIN user_account an ON n.author_id = an.id "
            . "LEFT JOIN blog_comment c ON n.id = c.news_id "
            . "LEFT JOIN user_account ac ON c.author_id = ac.id "
            . "WHERE n.ispublished = true AND n.slug = ? "
            . "ORDER BY c.createdat ASC";
    const SQL_LASTSANDSUB = "SELECT n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished, "
            . "an.id AS an_id, an.username AS an_username "
            . "FROM blog_news n "
            . "LEFT JOIN user_account an ON n.author_id = an.id "
            . "WHERE n.ispublished = true "
            . "ORDER BY n.createdat DESC "
            . "LIMIT ?";
    const SQL_CREATEDATDESC = "SELECT n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished, "
            . "an.id AS an_id, an.username AS an_username "
            . "FROM blog_news n LEFT JOIN user_account an ON n.author_id = an.id "
            . "ORDER BY n.createdat DESC";
    const SQL_INSERT = "INSERT into blog_news (id, author_id, title, slug, content, createdat, updatedat, ispublished) "
            . "VALUES (nextval('blog_news_id_seq'),?,?,?,?,?,?,?)";
    const SQL_UPDATE = "UPDATE blog_news SET author_id = ?, title = ?, slug = ?, content = ?, createdat = ?, updatedat = ?, ispublished = ? "
            . "WHERE id = ?";

    public static function hydrate($data = [], $key = "") {
        $news = new News();
        $news->setId($data[$key . "_id"])
                ->setTitle($data[$key . "_title"])
                ->setSlug($data[$key . "_slug"])
                ->setContent($data[$key . "_content"])
                ->setIsPublished($data[$key . "_ispublished"])
                ->setCreatedAt(\DateTime::createFromFormat("Y-m-d H:i:s", $data[$key . "_createdat"]))
                ->setUpdatedAt(\DateTime::createFromFormat("Y-m-d H:i:s", $data[$key . "_updatedat"]));

        if (isset($data["a" . $key . "_id"])) {
            $news->setAuthor(UserRepository::lightHydrateUser($data, "a" . $key));
        }

        return $news;
    }

    public function getIndex($limit, $offset) {
        $stmt = $this->dbal->prepare(self::SQL_INDEX);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $allNews = [];
        while ($res = $stmt->fetch()) {
            $allNews[] = self::hydrate($res, "n");
        }
        $stmt->closeCursor();

        return $allNews;
    }

    public function getByCreatedAtDESC() {
        $stmt = $this->dbal->query(self::SQL_CREATEDATDESC);

        $news = [];
        while ($res = $stmt->fetch()) {
            $news[] = self::hydrate($res, "n");
        }
        $stmt->closeCursor();

        return $news;
    }

    public function findOneAndCommBySlug(string $slug, bool $published = true) {

        //$stmt = $this->dbal->prepare(self::SQL_ONEANDCOMMBYSLUG);
        $filter = "WHERE n.slug = ? ";
        if ($published) {
            $filter = $filter . "AND n.ispublished = true ";
        }
        $filter = $filter . " ORDER BY c.createdat ASC";
        $sql = sprintf(self::SQL_FINDWITHCOMM, $filter);
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $slug, \PDO::PARAM_STR);
        $stmt->execute();

        $news = null;
        while ($res = $stmt->fetch()) {
            if ($news === null) {
                $news = self::hydrate($res, "n");
            }
            if ($res["c_id"]) {
                $news->addComment(CommentRepository::hydrate($res, "c"));
            }
        }
        $stmt->closeCursor();

        return $news;
    }

    public function findLasts($nbNews) {
        $stmt = $this->dbal->prepare(self::SQL_LASTSANDSUB);
        $stmt->bindValue(1, $nbNews, \PDO::PARAM_INT);
        $stmt->execute();

        $allNews = [];
        while ($res = $stmt->fetch()) {
            $allNews[] = self::hydrate($res, "n");
        }
        $stmt->closeCursor();

        return $allNews;
    }

    public function getTotalPublishedNumber() {
        $sql = "SELECT COUNT(n.id) FROM blog_news n WHERE n.ispublished = true";

        $stmt = $this->dbal->query($sql);
        $res = $stmt->fetch();
        $stmt->closeCursor();

        return $res["count"];
    }

    public function persist(News $news) {
        if ($news->getId() === null) {
            return $this->insert($news);
        }
        return $this->update($news);
    }

    private function insert(News $news) {
        $stmt = $this->dbal->prepare(self::SQL_INSERT);
        $stmt->bindValue(1, $news->getAuthor()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $news->getTitle(), \PDO::PARAM_STR);
        $stmt->bindValue(3, $news->getSlug(), \PDO::PARAM_STR);
        $stmt->bindValue(4, $news->getContent(), \PDO::PARAM_STR);
        $stmt->bindValue(5, $news->getCreatedAt()->format("Y-m-d H:i:s"), \PDO::PARAM_STR);
        $updated = $news->getUpdatedAt();
        if ($updated !== null) {
            $updated = $news->getUpdatedAt()->format("Y-m-d H:i:s");
        }
        $stmt->bindValue(6, $updated, \PDO::PARAM_STR);
        $stmt->bindValue(7, $news->getIsPublished(), \PDO::PARAM_BOOL);
        $stmt->execute();

        $news->setId($this->dbal->lastInsertId());

        $stmt->closeCursor();

        return $news;
    }

    private function update(News $news) {
        $stmt = $this->dbal->prepare(self::SQL_UPDATE);
        $stmt->bindValue(1, $news->getAuthor()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $news->getTitle(), \PDO::PARAM_STR);
        $stmt->bindValue(3, $news->getSlug(), \PDO::PARAM_STR);
        $stmt->bindValue(4, $news->getContent(), \PDO::PARAM_STR);
        $stmt->bindValue(5, $news->getCreatedAt()->format("Y-m-d H:i:s"), \PDO::PARAM_STR);
        $updated = $news->getUpdatedAt();
        if ($updated !== null) {
            $updated = $news->getUpdatedAt()->format("Y-m-d H:i:s");
        }
        $stmt->bindValue(6, $updated, \PDO::PARAM_STR);
        $stmt->bindValue(7, $news->getIsPublished(), \PDO::PARAM_BOOL);
        $stmt->bindValue(8, $news->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        $stmt->closeCursor();

        return $news;
    }

    public function remove(News $news) {
        $sql = "DELETE FROM blog_news WHERE id = ?";
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $news->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
    }

    public function create_database() {
        $schemaManager = $this->dbal->getSchemaManager();

        $fromSchema = $schemaManager->createSchema();

        $schema = clone $fromSchema;

        $table = $schema->createTable("blog_news");
        $table->addColumn("id", "integer", ["unsigned" => true]);
        $table->addColumn("author_id", "integer", ["unsigned" => true]);
        $table->addColumn("title", "string", ["length" => 255]);
        $table->addColumn("slug", "string", ["length" => 255]);
        $table->addColumn("content", "text");
        $table->addColumn("createdat", "datetime");
        $table->addColumn("updatedat", "datetime", ["notnull" => false]);
        $table->addColumn("ispublished", "boolean");

        $table->setPrimaryKey(["id"]);
        $table->addUniqueIndex(["slug"]);

        $schema->createSequence("blog_news_id_seq");

        $userTable = $schema->getTable("user_account");
        $table->addForeignKeyConstraint($userTable, ["author_id"], ["id"], ["onDelete" => "CASCADE"]);

        $sql = $fromSchema->getMigrateToSql($schema, $this->dbal->getDatabasePlatform());

        return $sql;
    }

}
