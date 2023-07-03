<?php

namespace dokuwiki\plugin\doi\Resolver;

abstract class AbstractIsbnResolver extends AbstractResolver
{
    public function __construct()
    {
        $this->defaultResult['idtype'] = 'ISBN';
        $this->defaultResult['type'] = 'book';
    }

    /** @inheritdoc */
    public function getFallbackURL($id)
    {
        return 'https://www.google.com/search?q=isbn+' . rawurlencode($id);
    }

    /** @inheritdoc */
    public function cleanID($id)
    {
        return preg_replace('/[^0-9X]/i', '', $id);
    }


}
