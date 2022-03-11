<?php

/**
 * DokuWiki plugin Struct Template inlinx syntax
 *
 * @author     Iain Hallam <iain@nineworlds.net>
 * @copyright  © 2022 Iain Hallam
 * @license    GPL-2.0-only (http://www.gnu.org/licenses/gpl-2.0.html)
 */

declare(strict_types=1);

use dokuwiki\plugin\structtemplate\meta\StructTemplateSyntax;

/**
 * Syntax plugin extending common Struct Template base class
 */
class syntax_plugin_structtemplate_inline extends StructTemplateSyntax
{
    /**
     * Define the precedence of this plugin to the parser
     *
     * @see  https://www.dokuwiki.org/devel:parser:getsort_list
     */
    public function getSort()
    {
        return 46;
    }

    /**
     * Define how this plugin handles paragraphs
     *
     * @see  https://www.dokuwiki.org/devel:syntax_plugins#ptype
     */
    public function getPType()
    {
        return 'normal';
    }

    /**
     * Connect lookup patterns to lexer
     *
     * @see  https://www.dokuwiki.org/devel:syntax_plugins#patterns
     *
     * @param  string  $lmode  Existing lexer mode
     */
    public function connectTo($lmode)
    {
        // Syntax:
        // <struct-template inline> OR <struct-template>
        // ---- data ----
        // [...]
        // ----
        // [...]
        // </struct-template>

        $re = '<' . self::TAG . ' +inline\b.*?>\n'
            . '----+ *data *----+\n'
            . '.*?\n'
            . '----+\n'
            . '.*?\n'
            . '<\/' . self::TAG . '>'
        ;
        $this->Lexer->addSpecialPattern(
            $re,                                  // regex
            $lmode,                               // lexer mode to use in
            'plugin_' . self::PLUGIN . '_inline'  // lexer mode to enter
        );

        $re = '<' . self::TAG . '\b.*?>\n'
            . '----+ *data *----+\n'
            . '.*?\n'
            . '----+\n'
            . '.*?\n'
            . '<\/' . self::TAG . '>'
        ;
        $this->Lexer->addSpecialPattern(
            $re,                                  // regex
            $lmode,                               // lexer mode to use in
            'plugin_' . self::PLUGIN . '_inline'  // lexer mode to enter
        );
    }
}
