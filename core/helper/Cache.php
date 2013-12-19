<?php
/**
 * User: SaWey
 * Date: 18/12/13
 */

namespace phpList;


class Cache {
    /**
     * @var Cache $_instance
     */
    private static $_instance;
    public $page_cache = array();
    public $url_cache = array();
    private $message_cache = array();
    private $linktrack_sent_cache = array();

    private function __construct()
    {
        $this->connect();
    }

    public static function instance()
    {
        if (!Cache::$_instance instanceof self) {
            Cache::$_instance = new self();
        }
        return Cache::$_instance;
    }

    public static function urlCache()
    {
        return Cache::instance()->url_cache;
    }

    public static function linktrackSentCache()
    {
        return Cache::instance()->linktrack_sent_cache;
    }

    /**
     * Get a message from cache, returns false when not available yet
     * @param Message $message
     * @return Message|bool
     */
    public static function getMessageFromCache($message)
    {
        if(!isset(Cache::$_instance->message_cache[$message->id])){
            return false;
        }
        return Cache::$_instance->message_cache[$message->id];
    }

    /**
     * Put a message in the cache
     * @param Message $message
     */
    public static function setMessageCache($message)
    {
        Cache::$_instance->message_cache[$message->id] = $message;
    }



    public static function getPageCache($url, $lastmodified = 0)
    {
        $req = phpList::DB()->Sql_Fetch_Row_Query(sprintf(
                'SELECT content FROM %s
                WHERE url = "%s"
                AND lastmodified >= %d',
                Config::getTableName('urlcache'),
                $url,
                $lastmodified
            ));
        return $req[0];
    }

    public static function getPageCacheLastModified($url)
    {
        $req = phpList::DB()->Sql_Fetch_Row_Query(sprintf(
                'SELECT lastmodified FROM %s
                WHERE url = "%s"',
                Config::getTableName('urlcache'),
                $url
            ));
        return $req[0];
    }

    public static function setPageCache($url, $lastmodified, $content)
    {
        #  if (isset($GLOBALS['developer_email'])) return;
        phpList::DB()->Sql_Query(sprintf(
                'DELETE FROM %s
                WHERE url = "%s"',
                Config::getTableName('urlcache'),
                $url
            ));
        phpList::DB()->Sql_Query(sprintf(
                'INSERT INTO %s (url,lastmodified,added,content)
                VALUES("%s",%d,CURRENT_TIMESTAMP,"%s")',
                Config::getTableName('urlcache'),
                $url,
                $lastmodified,
                addslashes($content)
            ));
    }

    public static function clearPageCache()
    {
        phpList::DB()->Sql_Query(sprintf(
            'DELETE FROM %s',
            Config::getTableName('urlcache')
            ));
    }

    public static function flushClickTrackCache() {
        if (count(Cache::$_instance->linktrack_sent_cache) == 0) return;
        foreach (Cache::$_instance->linktrack_sent_cache as $mid => $numsent) {
            foreach ($numsent as $fwdid => $fwdtotal) {
                //TODO: change output function
                //if (Config::VERBOSE)
                    //output("Flushing clicktrack stats for $mid: $fwdid => $fwdtotal");
                phpList::DB()->query(sprintf(
                        'UPDATE %s SET total = %d
                        WHERE messageid = %d
                        AND forwardid = %d',
                        Config::getTableName('linktrack_ml'),
                        $fwdtotal,
                        $mid,
                        $fwdid
                    ));
            }
        }
    }
} 