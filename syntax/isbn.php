<?php

/**
 * DokuWiki Plugin doi (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class syntax_plugin_doi_isbn extends syntax_plugin_doi_doi
{

    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('\[\[isbn>[^\]]+\]\]', $mode, 'plugin_doi_isbn');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $isbn = substr($match, 7, -2);
        return [
            'id' => $isbn,
        ];
    }

    /** @inheritDoc */
    protected function getResolver()
    {
        $class = '\\dokuwiki\\plugin\\doi\\Resolver\\Isbn'.$this->getConf('isbnresolver').'Resolver';
        return new $class();
    }


}

