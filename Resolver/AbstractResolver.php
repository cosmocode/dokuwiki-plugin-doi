<?php

namespace dokuwiki\plugin\doi\Resolver;

abstract class AbstractResolver
{


    protected $defaultResult = [
        'id' => '',
        'type' => '',
        'title' => '',
        'url' => '',
        'authors' => [],
        'publisher' => '',
        'published' => '',
        'journal' => '',
        'volume' => '',
        'issue' => '',
        'page' => '',
    ];

    /**
     * The extension used for the cache file
     *
     * Defaults to class name (without Resolver) in lowercase
     *
     * @return string
     */
    protected function getCacheExtension()
    {
        return '';
    }

    /**
     * Get a URL to use as a fallback if data fetching fails
     *
     * Should be a generic web serach for the given ID
     * 
     * @param string|int $id
     * @return string
     */
    abstract public function getFallbackURL($id);

    /**
     * Clean the given ID to a standard format
     * 
     * @param string $id
     * @return string|int
     */
    abstract public function cleanID($id);

    /**
     * Return the data in standard format
     *
     * @param string|int $id
     * @return array
     * @throws \Exception if getting data fails
     */
    abstract public function getData($id);

    /**
     * Fetch the info for the given ID
     *
     * @param string|int $id
     * @return false|array
     */
    abstract protected function fetchData($id);

    /**
     * Get the (potentially cached) data for the given ID
     *
     * Caches get refreshed when the resolver class has been updated
     *
     * @param string|int $id
     * @return array|false
     * @throws \Exception if getting data fails
     */
    protected function fetchCachedData($id)
    {
        $class = get_class($this);
        $class = substr($class, strrpos($class, '\\') + 1);
        $file = DOKU_PLUGIN . 'doi/Resolver/' . $class . '.php';

        $ext = trim($this->getCacheExtension(), '.');
        if ($ext === '') {
            $ext = strtolower(str_replace('Resolver', '', $class));
        }


        $cache = getCacheName($id, '.' . $ext . '.json');
        if (@filemtime($cache) > filemtime($file)) {
            return json_decode(file_get_contents($cache), true);
        }

        $result = $this->fetchData($id);
        file_put_contents($cache, json_encode($result));
        return $result;
    }
}
