<?php

/**
 * DokuWiki Plugin doi (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class syntax_plugin_doi extends \dokuwiki\Extension\SyntaxPlugin
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
        $this->Lexer->addSpecialPattern('\[\[doi>[^\]]+\]\]', $mode, 'plugin_doi');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $doi = substr($match, 6, -2);

        return ['doi' => $doi];
    }

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        $publication = $this->fetchInfo($data['doi']);
        $title = $publication['message']['title'][0] ?? $data['doi'];
        $url = $publication['message']['URL'] ?? 'https://doi.org/' . $data['doi'];

        if ($mode !== 'xhtml' || !$publication) {
            $renderer->externallink($url, $title);
            return true;
        }

        /** @var Doku_Renderer_xhtml $renderer */
        $this->formatPub($publication['message'], $renderer);

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
        $doi = $message['DOI'];
        $title = $message['title'][0] ?? $doi;
        $url = $message['URL'] ?? 'https://doi.org/' . $doi;

        $class = hsc($message['type']);

        $authorList = [];
        foreach ($message['author'] ?? [] as $author) {
            $authorList[] = '<strong>' . hsc($author['family']) . '</strong>';
        }

        if (isset($message['container-title'][0])) {
            $journal = $message['container-title'][0];
            $journal .= ' ' . join('/', [$message['volume'] ?? null, $message['issue'] ?? null]);
            $journal = '<span>' . hsc($journal) . '</span>';
            if (isset($message['page'])) {
                $journal .= ' <i>p' . hsc($message['page']) . '</i>';
            }
            $journal = ' <span class="journal">' . $journal . '</span>';
        } else {
            $journal = '';
        }

        $published = $message['issued']['date-parts'][0][0] ?? '';
        if ($published) $published = ' <span>(' . hsc($published) . ')</span>';

        $publisher = hsc($message['publisher'] ?? '');

        //output
        $renderer->doc .= '<div class="plugin_doi ' . $class . '">';
        $renderer->externallink($url, $title);
        $renderer->doc .= $published;

        $renderer->doc .= '<div class="meta">';
        if ($authorList) {
            $renderer->doc .= '<span class="authors">' . join(', ', $authorList) . '</span>';
        }
        if ($journal) {
            $renderer->doc .= $journal;
        }
        $renderer->doc .= '</div>';

        $renderer->doc .= '<div class="meta">';
        if ($publisher) {
            $renderer->doc .= '<span class="publisher">' . $publisher . '</span>';
        }
        $renderer->doc .= ' <code class="doi">DOI:' . $doi . '</code>';
        $renderer->doc .= '</div>';

        $renderer->doc .= '</div>';
    }

    /**
     * Fetch the info for the given DOI
     *
     * @param string $doi
     * @return false|array
     */
    protected function fetchInfo($doi)
    {
        $http = new \dokuwiki\HTTP\DokuHTTPClient();

        $json = $http->get('https://api.crossref.org/works/' . $doi);
        if (!$json) return false;

        return json_decode($json, true);
    }
}

