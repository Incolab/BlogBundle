<?php

/**
 * Repository File
 * 
 * @author David Salbei <david@incolab.fr>
 * @copyright 2017 Incolab
 * @licence https://opensource.org/licenses/MIT MIT
 * 
 */

namespace Incolab\BlogBundle\Repository;

use Incolab\DBALBundle\Manager\Manager;
use Incolab\BlogBundle\Repository\NewsSQL;
use Incolab\BlogBundle\Entity\News;
use UserBundle\Repository\UserRepository;
use Incolab\BlogBundle\Repository\CommentRepository;

/**
 * Repository fro the News Entity
 *
 * @author David Salbei <david@incolab.fr>
 * @copyright 2017 Incolab
 * @licence https://opensource.org/licenses/MIT MIT
 */
class NewsRepository extends Manager {

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
        $sql = sprintf(NewsSQL::SQL_FIND, "WHERE n.ispublished = true ORDER BY n.createdat DESC LIMIT ? OFFSET ?");
        $stmt = $this->dbal->prepare($sql);
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
        $sql = sprintf(NewsSQL::SQL_FIND, "ORDER BY n.createdat DESC");
        $stmt = $this->dbal->query($sql);

        $news = [];
        while ($res = $stmt->fetch()) {
            $news[] = self::hydrate($res, "n");
        }
        $stmt->closeCursor();

        return $news;
    }

    public function findOneAndCommBySlug(string $slug, bool $published = true) {
        $filter = "WHERE n.slug = ? ";
        if ($published) {
            $filter = $filter . "AND n.ispublished = true ";
        }
        $filter = $filter . " ORDER BY c.createdat ASC";
        $sql = sprintf(NewsSQL::SQL_FINDALL, $filter);
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
        $sql = sprintf(NewsSQL::SQL_FIND, "WHERE n.ispublished = true ORDER BY n.createdat DESC LIMIT ?");
        $stmt = $this->dbal->prepare($sql);
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
        $stmt = $this->dbal->prepare(NewsSQL::SQL_INSERT);
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
        $stmt = $this->dbal->prepare(NewsSQL::SQL_UPDATE);
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
