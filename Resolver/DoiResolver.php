<?php

namespace dokuwiki\plugin\doi\Resolver;

use dokuwiki\HTTP\DokuHTTPClient;

class DoiResolver extends AbstractResolver
{

    /** @inheritdoc */
    public function getData($id)
    {
        $data = $this->fetchCachedData($id);
        $result = $this->defaultResult;

        $result['id'] = $data['DOI'];
        $result['title'] = empty($data['title']) ? $id : $data['title'];
        $result['url'] = $data['URL'] ?? 'https://doi.org/' . $id;
        $result['url'] = preg_replace('/^http:/', 'https:', $result['url']); // always use https
        $result['type'] = $data['type'];
        $result['idtype'] = 'DOI';

        foreach ($data['author'] ?? $data['editor'] ?? [] as $author) {
            $result['authors'][] = $author['given'] . ' ' . $author['family'];
        }

        $result['journal'] = $data['container-title'] ?? '';
        $result['volume'] = $data['volume'] ?? '';
        $result['issue'] = $data['issue'] ?? '';
        $result['page'] = $data['page'] ?? '';

        $result['published'] = $data['issued']['date-parts'][0][0] ?? '';
        $result['publisher'] = $data['publisher'] ?? '';

        return $result;
    }

    /** @inheritdoc */
    protected function fetchData($id)
    {
        $http = new DokuHTTPClient();
        $http->headers['Accept'] = 'application/vnd.citationstyles.csl+json';
        $json = $http->get('https://doi.org/' . $id);
        if (!$json) throw new \Exception('Could not fetch data from doi.org. ' . $http->error);
        return json_decode($json, true);
    }

    /** @inheritdoc */
    public function getFallbackURL($id)
    {
        return 'https://doi.org/' . $id;
    }

    /** @inheritdoc */
    public function cleanID($id)
    {
        return trim($id, ' /.');
    }
}
