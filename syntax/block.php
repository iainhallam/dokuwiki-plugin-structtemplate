<?php

/**
 * DokuWiki plugin Struct Template block syntax
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
class syntax_plugin_structtemplate_block extends StructTemplateSyntax
{
    /**
     * Define how this plugin handles paragraphs
     *
     * @see  https://www.dokuwiki.org/devel:syntax_plugins#ptype
     */
    public function getPType()
    {
        return 'block';
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
        // <struct-template block> OR <STRUCT-TEMPLATE>
        // ---- data ----
        // [...]
        // ----
        // [...]
        // </struct-template> OR </STRUCT-TEMPLATE> respectively

        $re = '<' . self::TAG . ' +block\b.*?>\n'
            . '----+ *data *----+\n'
            . '.*?\n'
            . '----+\n'
            . '.*?\n'
            . '<\/' . self::TAG . '>'
        ;
        $this->Lexer->addSpecialPattern(
            $re,                                 // regex
            $lmode,                              // lexer mode to use in
            'plugin_' . self::PLUGIN . '_block'  // lexer mode to enter
        );

        $re = '<' . strtoupper(self::TAG) . '\b.*?>\n'
            . '----+ *data *----+\n'
            . '.*?\n'
            . '----+\n'
            . '.*?\n'
            . '<\/' . strtoupper(self::TAG) . '>'
        ;
        $this->Lexer->addSpecialPattern(
            $re,                                 // regex
            $lmode,                              // lexer mode to use in
            'plugin_' . self::PLUGIN . '_block'  // lexer mode to enter
        );
    }
}
