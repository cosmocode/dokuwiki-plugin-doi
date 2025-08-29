<?php

use dokuwiki\plugin\doi\Resolver\AbstractResolver;
use \dokuwiki\plugin\doi\Resolver\DoiResolver;


/**
 * DokuWiki Plugin doi (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class syntax_plugin_doi_doi extends \dokuwiki\Extension\SyntaxPlugin
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
        $this->Lexer->addSpecialPattern('\[\[doi>[^\]]+\]\]', $mode, 'plugin_doi_doi');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $match = substr($match, 2, -2);
        list(, $id) = sexplode('>', $match, 2);
        list($id, $title) = sexplode('|', $id, 2);
        return [
            'id' => $id,
            'title' => $title,
        ];
    }

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        $resolver = $this->getResolver();
        $data['id'] = $resolver->cleanID($data['id']);

        try {
            $publication = $resolver->getData($data['id']);
        } catch (Exception $e) {
            msg(hsc($e->getMessage()), -1);
            $url = $resolver->getFallbackURL($data['id']);
            $title = empty($data['title']) ? $data['id'] : $data['title'];

            $renderer->externallink($url, $title);
            return true;
        }

        // overwritten title?
        if (!empty($data['title'])) $publication['title'] = $data['title'];

        // overwritten url (eg. by amazonlight plugin)?
        if (!empty($data['url'])) $publication['url'] = $data['url'];

        if ($mode === 'xhtml') {
            /** @var Doku_Renderer_xhtml $renderer */
            $this->renderXHTML($publication, $renderer);
        } else {
            $this->renderAny($publication, $renderer);
        }

        return true;
    }

    /**
     * @return AbstractResolver
     */
    protected function getResolver()
    {
        return new DoiResolver();
    }

    /**
     * Render the given data on the XHTML renderer
     *
     * Adds various classes to the output for CSS styling
     *
     * @param array $data
     * @param Doku_Renderer_xhtml $renderer
     * @return void
     */
    protected function renderXHTML($data, $renderer)
    {
        $renderer->doc .= '<div class="plugin_doi ' . hsc($data['type']) . '">';

        if( $this->getConf('cover') && $data['image'] ) {
            $renderer->externallink(
                $data['url'],
                [
                    'src' => $data['image'],
                    'title' => $data['title'],
                    'align' => 'left',
                    'width' => 64,
                    'height' => 90,
                    'cache' => true,
                    'type' => 'externalmedia'
                ]
            );
        }

        $renderer->externallink($data['url'], $data['title']);

        if ($data['published']) {
            $renderer->doc .= ' <span>(' . hsc($data['published']) . ')</span>';
        }

        $renderer->doc .= '<div class="meta">';
        if ($data['authors']) {
            $authors = array_map(function ($author) {
                return '<strong>' . hsc($author) . '</strong>';
            }, $data['authors']);
            $renderer->doc .= '<span class="authors">' . join(', ', $authors) . '</span>';
        }
        if ($data['journal']) {
            $journal = $data['journal'];
            $journal .= ' ' . join('/', array_filter([$data['volume'] ?? null, $data['issue'] ?? null]));
            $journal = '<span>' . hsc($journal) . '</span>';
            if ($data['page']) {
                $journal .= ' <i>p' . hsc($data['page']) . '</i>';
            }
            $renderer->doc .= ' <span class="journal">' . $journal . '</span>';
        }
        $renderer->doc .= '</div>';

        $renderer->doc .= '<div class="meta">';
        if ($data['publisher']) {
            $renderer->doc .= '<span class="publisher">' . hsc($data['publisher']) . '</span>';
        }
        $renderer->doc .= ' <code class="id">' . $data['idtype'] . ':' . hsc($data['id']) . '</code>';
        $renderer->doc .= '</div>';

        $renderer->doc .= '</div>';
    }

    /**
     * Render the given data on any renderer
     *
     * Uses renderer methods only
     *
     * @param array $data
     * @param Doku_Renderer $renderer
     * @return void
     */
    protected function renderAny($data, $renderer)
    {
        $renderer->p_open();
        $renderer->externallink($data['url'], $data['title']);

        if ($data['published']) {
            $renderer->cdata(' (' . hsc($data['published']) . ')');
        }
        $renderer->linebreak();

        if ($data['authors']) {
            $len = count($data['authors']);
            for ($i = 0; $i < $len; $i++) {
                $renderer->strong_open();
                $renderer->cdata($data['authors'][$i]);
                $renderer->strong_close();
                if ($i < $len - 1) {
                    $renderer->cdata(', ');
                }
            }

            if ($data['journal']) {
                $journal = $data['journal'];
                $journal .= ' ' . join('/', array_filter([$data['volume'] ?? null, $data['issue'] ?? null]));
                $renderer->cdata(' ' . $journal);
            }

            if ($data['page']) {
                $renderer->cdata(' ');
                $renderer->emphasis_open();
                $renderer->cdata('p' . $data['page']);
                $renderer->emphasis_close();
            }
        }
        $renderer->linebreak();

        if ($data['publisher']) {
            $renderer->cdata($data['publisher']);
            $renderer->cdata(' ');
        }
        $renderer->monospace_open();
        $renderer->cdata($data['idtype'] . ':' . hsc($data['id']));
        $renderer->monospace_close();

        $renderer->p_close();
    }
}

