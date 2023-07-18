<?php

namespace dokuwiki\plugin\doi\Resolver;

use dokuwiki\HTTP\DokuHTTPClient;

/**
 * ISBN resolver using the Open Library API
 */
class IsbnOpenLibraryResolver extends AbstractIsbnResolver
{
    /** @inheritdoc */
    public function getData($id)
    {
        $message = $this->fetchCachedData($id);
        $result = $this->defaultResult;

        $result['url'] = $message['info_url'];
        $message = $message['details'];

        $result['id'] = $message['isbn_13'][0] ?? $message['isbn_10'][0] ?? '';
        $result['title'] = $message['full_title'] ?? $message['title'] ?? $id;
        if(empty($result['title'])) $result['title'] = $id;

        $result['authors'] = array_map(function ($author) {
            return $author['name'];
        }, $message['authors'] ?? []);

        $published = $message['publish_date'] ?? '';
        if (preg_match('/\b(\d{4})\b/', $published, $m)) {
            $result['published'] = $m[1];
        }
        $result['publisher'] = $message['publishers'][0] ?? '';

        return $result;
    }

    /** @inheritdoc */
    protected function fetchData($id)
    {
        $http = new DokuHTTPClient();
        $json = $http->get('https://openlibrary.org/api/books?jscmd=details&format=json&bibkeys=ISBN:' . $id);
        if (!$json) throw new \Exception('Could not fetch data from Open Library. ' . $http->error);
        $data = json_decode($json, true);
        if (!count($data)) throw new \Exception('No ISBN results found at Open Library.');
        return array_shift($data); // first entry
    }
}
