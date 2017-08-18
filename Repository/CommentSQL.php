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
 * SQL strings for CommentRepository
 *
 * @author David Salbei <david@incolab.fr>
 * @copyright 2017 Incolab
 * @licence https://opensource.org/licenses/MIT MIT
 */
class CommentSQL
{

    /**
     * Find
     */
    const SQL_FIND = "SELECT c.id AS c_id, c.author_id AS c_author_id, c.content AS c_content, c.createdat AS c_createdat,"
            . "ac.id AS ac_id, ac.username AS ac_username, "
            . "n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished "
            . "FROM blog_comment c "
            . "%s";

    /**
     * Find with News
     */
    const SQL_FINDALL = "SELECT c.id AS c_id, c.author_id AS c_author_id, c.content AS c_content, c.createdat AS c_createdat,"
            . "ac.id AS ac_id, ac.username AS ac_username, "
            . "n.id AS n_id, n.author_id AS n_author_id, n.title AS n_title, n.slug AS n_slug, "
            . "n.content AS n_content, n.createdat AS n_createdat, n.updatedat AS n_updatedat, n.ispublished AS n_ispublished "
            . "FROM blog_comment c "
            . "LEFT JOIN user_account ac ON c.author_id = ac.id "
            . "LEFT JOIN blog_news n ON c.news_id = n.id "
            . "%s";

    /**
     * Insert
     */
    const SQL_INSERT = "INSERT INTO blog_comment (id, author_id, news_id, content, createdat) "
            . "VALUES (nextval('blog_comment_id_seq'),?,?,?,?)";

    /**
     * Update
     */
    const SQL_UPDATE = "UPDATE blog_comment SET author_id = ?, news_id = ?, content = ?, createdat = ? "
            . "WHERE id = ?";

}
