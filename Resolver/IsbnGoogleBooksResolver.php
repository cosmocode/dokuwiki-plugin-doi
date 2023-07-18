<?php

namespace dokuwiki\plugin\doi\Resolver;

use dokuwiki\HTTP\DokuHTTPClient;

/**
 * ISBN resolver using Google Books API
 */
class IsbnGoogleBooksResolver extends AbstractIsbnResolver
{

    /** @inheritdoc */
    public function getData($id)
    {
        $data = $this->fetchCachedData($id);
        $data = $data['volumeInfo'];
        $result = $this->defaultResult;

        $result['url'] = $data['canonicalVolumeLink'];
        foreach ($data['industryIdentifiers'] as $identifier) {
            if ($identifier['type'] === 'ISBN_13') {
                $result['id'] = $identifier['identifier'];
                break;
            }
            if ($identifier['type'] === 'ISBN_10') {
                $result['id'] = $identifier['identifier'];
            }
        }

        $result['title'] = $data['title'];
        if (isset($data['subtitle'])) $result['title'] .= ': ' . $data['subtitle'];
        if(empty($result['title'])) $result['title'] = $id;

        $result['authors'] = $data['authors'] ?? [];

        $published = $data['publishedDate'] ?? '';
        if (preg_match('/\b(\d{4})\b/', $published, $m)) {
            $result['published'] = $m[1];
        }

        $result['publisher'] = $data['publisher'] ?? '';

        return $result;
    }

    /** @inheritdoc */
    protected function fetchData($id)
    {
        $http = new DokuHTTPClient();
        $json = $http->get('https://www.googleapis.com/books/v1/volumes?q=isbn:' . $id);
        if (!$json) throw new \Exception('Could not fetch data from Google Books. ' . $http->error);
        $data = json_decode($json, true);
        if (!isset($data['items'])) throw new \Exception('No ISBN results found at Google Books.');
        return $data['items'][0]; // first entry
    }
}
