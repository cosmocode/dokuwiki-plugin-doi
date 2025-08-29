<?php

namespace dokuwiki\plugin\doi\Resolver;

use dokuwiki\HTTP\DokuHTTPClient;

/**
 * ISBN resolver scraping isdn.de
 */
class IsbnIsbnDeResolver extends AbstractIsbnResolver
{
    /** @inheritdoc */
    public function getFallbackURL($id)
    {
        return 'https://www.isbn.de/buecher/suche/' . rawurlencode($id);
    }

    /** @inheritdoc */
    public function getData($id)
    {
        return $this->fetchCachedData($id);
    }

    /** @inheritdoc */
    protected function fetchData($id)
    {
        $http = new DokuHTTPClient();
        $url = $this->getFallbackURL($id);


        $html = $http->get($url);
        if (!$html) throw new \Exception('Could not fetch data from isdn.de. ' . $http->error);

        $data = $this->defaultResult;

        $data['id'] = $this->extract('/<meta property="og:book:isbn" content="([^"]+)"/', $html);
        if (!$data['id']) throw new \Exception('ISBN not found at isdn.de.');
        $data['url'] = $this->extract('/<meta property="og:url" content="([^"]+)"/', $html);

        $data['title'] = $this->extract('/<meta property="og:title" content="([^"]+)"/', $html);
        if(empty($data['title'])) $data['title'] = $id;
        $data['published'] = $this->extract('/<meta property="og:book:release_date" content="((\d){4})[^"]+"/', $html);

        $data['authors'] = $this->extractAll('/<a href="\/person\/.*?">(.+?)<\/a>/', $html);
        $data['publisher'] = $this->extract('/<a href="\/verlag\/.*?">(.+?)<\/a>/', $html);

        $data['image'] = $this->extract('/<meta property="og:image" content="([^"]+)"/', $html);

        return $data;
    }

    /**
     * Extract a value from a HTML string using a regex
     *
     * @param string $regex
     * @param string $html
     * @param int $group
     * @return string
     */
    protected function extract($regex, $html, $group = 1)
    {
        if (preg_match($regex, $html, $m)) {
            return html_entity_decode($m[$group]);
        }
        return '';
    }

    /**
     * Extract all matching values from a HTML string using a regex
     *
     * @param string $regex
     * @param string $html
     * @param int $group
     * @return string
     */
    protected function extractAll($regex, $html, $group = 1)
    {
        if (preg_match_all($regex, $html, $m)) {
            $all = $m[$group];
            $all = array_map('html_entity_decode', $all);
            $all = array_unique($all);
            return $all;
        }
        return [];
    }
}
