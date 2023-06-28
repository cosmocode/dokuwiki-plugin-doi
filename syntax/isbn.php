<?php

/**
 * DokuWiki Plugin doi (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class syntax_plugin_doi_isbn extends \dokuwiki\Extension\SyntaxPlugin
{
    /** @inheritDoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'normal';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 155;
    }

    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('\[\[isbn>[^\]]+\]\]', $mode, 'plugin_doi_isbn');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $doi = substr($match, 7, -2);

        return ['isbn' => $doi];
    }

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        $publication = $this->fetchInfo($data['isbn']);
        $title = $publication['details']['title'] ?? $data['isbn'];
        $url = $publication['details']['info_url'] ?? 'https://www.google.com/search?q=isbn+' . $data['isbn'];

        if ($mode !== 'xhtml' || !$publication) {
            $renderer->externallink($url, $title);
            return true;
        }

        /** @var Doku_Renderer_xhtml $renderer */
        $this->formatPub($publication, $renderer);

        return true;
    }

    /**
     * Render the given message
     *
     * @param array $message
     * @param Doku_Renderer_xhtml $renderer
     * @return void
     */
    protected function formatPub($message, $renderer)
    {
        $url = $message['info_url'];
        $message = $message['details'];

        $isbn = $message['isbn_13'][0] ?? $message['isbn_10'][0] ?? '';
        $title = $message['title'] ?? $isbn;

        $class = 'book';

        $authorList = [];
        foreach ($message['authors'] ?? [] as $author) {
            $authorList[] = '<strong>' . hsc($author['name']) . '</strong>';
        }

        $published = $message['publish_date'] ?? '';
        if (preg_match('/\b(\d{4})\b/', $published, $m)) {
            $published = ' <span>(' . hsc($m[1]) . ')</span>';
        } else {
            $published = '';
        }

        $publisher = hsc($message['publishers'][0] ?? '');

        //output
        $renderer->doc .= '<div class="plugin_doi ' . $class . '">';
        $renderer->externallink($url, $title);
        $renderer->doc .= $published;

        $renderer->doc .= '<div class="meta">';
        if ($authorList) {
            $renderer->doc .= '<span class="authors">' . join(', ', $authorList) . '</span>';
        }
        $renderer->doc .= '</div>';

        $renderer->doc .= '<div class="meta">';
        if ($publisher) {
            $renderer->doc .= '<span class="publisher">' . $publisher . '</span>';
        }
        $renderer->doc .= ' <code class="isbn">ISBN:' . $isbn . '</code>';
        $renderer->doc .= '</div>';

        $renderer->doc .= '</div>';
    }

    /**
     * Fetch the info for the given ISBN
     *
     * @param string $isbn
     * @return false|array
     */
    protected function fetchInfo($isbn)
    {
        $cache = getCacheName($isbn, '.isbn.json');
        if (@filemtime($cache) > filemtime(__FILE__)) {
            $data = json_decode(file_get_contents($cache), true);
        } else {
            $http = new \dokuwiki\HTTP\DokuHTTPClient();
            $json = $http->get('https://openlibrary.org/api/books?jscmd=details&format=json&bibkeys=ISBN:' . $isbn);
            if (!$json) return false;

            file_put_contents($cache, $json);
            $data = json_decode($json, true);
        }
        return array_shift($data); // first entry
    }
}

