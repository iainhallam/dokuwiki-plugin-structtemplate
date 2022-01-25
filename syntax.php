<?php

/**
 * DokuWiki plugin Struct Template
 *
 * @author     Iain Hallam <iain@nineworlds.net>
 * @copyright  © 2022 Iain Hallam
 * @license    GPL-2.0-only (http://www.gnu.org/licenses/gpl-2.0.html)
 */

declare(strict_types=1);

use dokuwiki\plugin\struct\meta\Column;
use dokuwiki\plugin\struct\meta\ConfigParser;
use dokuwiki\plugin\struct\meta\SearchConfig;
use dokuwiki\plugin\struct\meta\StructException;
use dokuwiki\plugin\struct\meta\Value;

/**
 * Syntax plugin extending standard DokuWiki class
 */
class syntax_plugin_structtemplate extends DokuWiki_Syntax_Plugin
{
    /** @var  string  The tag to be used in Wiki markup between < and > */
    public const TAG    = 'struct-template';
    /** @var  string  The system name of the plugin */
    public const PLUGIN = 'structtemplate';

    /**
     * Define the type of syntax plugin
     *
     * @see  https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    public function getType()
    {
        return 'substition';
    }

    // /**
    //  * Define the allowed types of syntax within this plugin
    //  *
    //  * @see  https://www.dokuwiki.org/devel:syntax_plugins#allowed_modes
    //  */
    // public function getAllowedTypes()
    // {
    //     return [
    //         'container',
    //         'disabled',
    //         'formatting',
    //         'paragraphs',
    //         'protected',
    //         'substition',
    //     ];
    // }

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
     * Define the precedence of this plugin to the parser
     *
     * @see  https://www.dokuwiki.org/devel:parser:getsort_list
     */
    public function getSort()
    {
        return 45;
    }

    /**
     * Connect lookup patterns to lexer
     *
     * Syntax:
     * <struct-template>
     * ---- data ----
     * [...]
     * ----
     * [...]{{$$schema.field ? raw}}[...]
     * </struct-template>
     *
     * @see  https://www.dokuwiki.org/devel:syntax_plugins#patterns
     *
     * @param  string  $lmode  Existing lexer mode
     */
    public function connectTo($lmode)
    {
        // The opening tag and lookup, and lookahead to ensure closing tag
        $pattern = '<' . self::TAG . '>\n*----+ *data *-+\n.*?\n----+.*?<\/' . self::TAG . '>';

        $this->Lexer->addSpecialPattern(
            $pattern,                 // regex
            $lmode,                   // lexer mode to use in
            'plugin_' . self::PLUGIN  // lexer mode to enter
        );
    }

    /**
     * Handle matches
     *
     * @see  https://www.dokuwiki.org/devel:syntax_plugins#handle_method
     *
     * @param   string  $match     Text matched by the patterns
     * @param   int     $state     Lexer state for the match
     * @param   int     $position  Character position of the matched text
     * @param   object  $handler   Doku_Handler object
     * @return  array              Data for render()
     */
    public function handle($match, $state, $position, Doku_Handler $handler): array
    {
        // Access global configuration settings
        global $conf;

        $template_start_index = 0;
        // Reduce match to Struct search config
        $lines = explode("\n", $match);
        // Ignore first two lines (tag and data header) and last line (closing tag)
        for ($line_index = 2; $line_index <= count($lines) - 1; $line_index++) {
            if (preg_match('/^----+$/', $lines[$line_index])) {
                // Reached the end of the data block
                $template_start_index = $line_index + 1;
                break;
            }

            $struct_syntax[] = $lines[$line_index];
        }

        // Extract template, ignoring last line containing closing tag
        $template = implode("\n", array_slice($lines, $template_start_index, -1));

        try {
            $parser = new ConfigParser($struct_syntax);
            $config = $parser->getConfig();
        } catch (StructException $e) {
            msg($e->getMessage(), -1, $e->getLine(), $e->getFile());
            if ($conf['allowdebug']) {
                msg('<pre>' . hsc($e->getTraceAsString()) . '</pre>', -1);
            }
            return [];
        }

        return [$config, $template];
    }

    /**
     * Output rendered matches
     *
     * @see  https://www.dokuwiki.org/devel:syntax_plugins#render_method
     *
     * @param   string  $mode      Output format to generate
     * @param   object  $renderer  Doku_Renderer object
     * @param   array   $data      Data created by handle()
     * @return  bool               Whether the syntax rendered OK
     */
    public function render($mode, Doku_Renderer $renderer, $data): bool
    {
        // Access global configuration settings
        global $conf;

        // Unpack data from handler
        $config   = $data[0];
        $template = $data[1];

        // Run the search (can't be in handler as that is cached)
        try {
            $search = new SearchConfig($config);

            // Get all matching data, no pagination
            $search->setLimit(0);
            $search->setOffset(0);

            // Run the search
            $struct_data  = $search->execute();
            $this->n_rows = $search->getCount();
        } catch (StructException $e) {
            msg($e->getMessage(), -1, $e->getLine(), $e->getFile());
            if ($conf['allowdebug']) {
                msg('<pre>' . hsc($e->getTraceAsString()) . '</pre>', -1);
            }
            return false;
        }

        // Construct a lookup table for column names and indices in the result
        $columns = $search->getColumns();
        foreach ($columns as $column) {
            // getFullColumnName takes false to disable enforceSingleColumn
            // - 1 because columns are 1-indexed, while arrays are 0-indexed
            $column_indices[$column->getFullQualifiedLabel(false)] = $column->getColref() - 1;
        }

        foreach ($struct_data as $row_index => $row) {
            $chunks = explode('{{$', $template);

            // First entry contains no fields
            $interpolated = $chunks[0];
            $chunks = array_slice($chunks, 1);

            foreach ($chunks as $chunk) {
                // Since the string was exploded on {{$, this must start with a field
                $chunk_parts    = explode('}}', $chunk, 2);
                $column_request = $chunk_parts[0];
                $next_output    = $chunk_parts[1];

                if (array_key_exists($column_request, $column_indices)) {
                    $interpolated .= $row[$column_indices[$column_request]]->getDisplayValue();
                } else {
                    if ($this->getConf('show_not_found')) {
                        $this->renderer->cdata($this->helper->getLang('none'));
                    }
                }
                $interpolated .= $next_output;
            }

            // Send to document
            $renderer->doc .= $interpolated;
        }

        return true;
    }
}
