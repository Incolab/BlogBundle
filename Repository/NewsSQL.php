<?php

/**
 * File that contains SQL Strings
 * 
 * @author David Salbei <david@incolab.fr>
 * @copyright 2017 Incolab
 * @licence https://opensource.org/licenses/MIT MIT
 * 
 */

namespace Incolab\BlogBundle\Repository;

/**
 * SQL strings for NewsRepository
 *
 * @author David Salbei <david@incolab.fr>
 * @copyright 2017 Incolab
 * @licence https://opensource.org/licenses/MIT MIT
 */
class NewsSQL
{

    /**
     * Find
     */
    const SQL_FIND = "SELECT n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished, "
            . "an.id AS an_id, an.username AS an_username "
            . "FROM blog_news n "
            . "LEFT JOIN user_account an ON n.author_id = an.id "
            . "%s";

    /**
     * Find with Comment
     */
    const SQL_FINDALL = "SELECT n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished, "
            . "an.id AS an_id, an.username AS an_username, "
            . "c.id AS c_id, c.createdat AS c_createdat, c.content AS c_content, "
            . "ac.id AS ac_id, ac.username AS ac_username "
            . "FROM blog_news n "
            . "LEFT JOIN user_account an ON n.author_id = an.id "
            . "LEFT JOIN blog_comment c ON n.id = c.news_id "
            . "LEFT JOIN user_account ac ON c.author_id = ac.id "
            . "%s";

    /**
     * Insert
     */
    const SQL_INSERT = "INSERT into blog_news (id, author_id, title, slug, content, createdat, updatedat, ispublished) "
            . "VALUES (nextval('blog_news_id_seq'),?,?,?,?,?,?,?)";
    /*
     * Update
     */
    const SQL_UPDATE = "UPDATE blog_news SET author_id = ?, title = ?, slug = ?, content = ?, createdat = ?, updatedat = ?, ispublished = ? "
            . "WHERE id = ?";

}
