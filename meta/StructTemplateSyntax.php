<?php

/**
 * DokuWiki plugin Struct Template generic syntax
 *
 * @author     Iain Hallam <iain@nineworlds.net>
 * @copyright  © 2022 Iain Hallam
 * @license    GPL-2.0-only (http://www.gnu.org/licenses/gpl-2.0.html)
 */

declare(strict_types=1);

namespace dokuwiki\plugin\structtemplate\meta;

use Doku_Handler;
use Doku_Renderer;
use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\plugin\struct\meta\ConfigParser;
use dokuwiki\plugin\struct\meta\SearchConfig;
use dokuwiki\plugin\struct\meta\StructException;

/**
 * Syntax plugin extending standard DokuWiki class
 */
class StructTemplateSyntax extends SyntaxPlugin
{
    /** @var  string  TAG             The tag to be used in Wiki markup between < and > */
    /** @var  string  PLUGIN          The system name of the plugin */
    /** @var  string  OPEN_SYNTAX     Interpolation syntax */
    /** @var  string  CLOSE_SYNTAX    Interpolation syntax */
    public const TAG            = 'struct-template';
    public const PLUGIN         = 'structtemplate';
    public const OPEN_SYNTAX    = '{{$$';
    public const CLOSE_SYNTAX   = '}}';

    /**
     * Define the type of syntax plugin
     *
     * @see  https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    public function getType()
    {
        return 'substition';
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
        // Configuration
        // -------------------------------------------------------------

        // Access global configuration settings
        global $conf;
        // Disable section editing for the template
        $old_maxseclevel = $conf['maxseclevel'];
        $conf['maxseclevel'] = 0;

        // Extract the data block and template
        // -------------------------------------------------------------

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
        // -1: ignore last line containing closing tag
        $template = implode("\n", array_slice($lines, $template_start_index, -1));

        // Configure the Struct search
        // -------------------------------------------------------------

        try {
            $parser = new ConfigParser($struct_syntax);
            $search_config = $parser->getConfig();
        } catch (StructException $e) {
            msg($e->getMessage(), -1, $e->getLine(), $e->getFile());
            if ($conf['allowdebug']) {
                msg('<pre>' . hsc($e->getTraceAsString()) . '</pre>', -1);
            }
            // Re-enable section editing
            $conf['maxseclevel'] = $old_maxseclevel;
            return [];
        }

        // Return data for rendering
        // -------------------------------------------------------------

        // Re-enable section editing
        $conf['maxseclevel'] = $old_maxseclevel;

        return [$search_config, $template];
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
        $search_config = $data[0];
        $template      = $data[1];

        // Access global configuration settings
        global $conf;

        // Disable section editing for the template
        $old_maxseclevel = $conf['maxseclevel'];
        $conf['maxseclevel'] = 0;

        // Run the search (can't be in handler as that is cached)
        try {
            $search = new SearchConfig($search_config);

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

            // Re-enable section editing
            $conf['maxseclevel'] = $old_maxseclevel;

            return false;
        }

        // Construct a lookup table for column names and indices in the result
        $columns = $search->getColumns();
        foreach ($columns as $index => $column) {
            $column_id = $column->getFullQualifiedLabel(false);
            // getFullColumnName takes false to disable enforceSingleColumn
            $column_indices[$column_id] = $index;
        }

        foreach ($struct_data as $row_index => $row) {
            $chunks = explode(self::OPEN_SYNTAX, $template);

            // First entry contains no fields
            $interpolated = $chunks[0];
            $chunks = array_slice($chunks, 1);

            foreach ($chunks as $chunk) {
                // Since the string was exploded on the open marker, this must start with a field
                $chunk_parts    = explode(self::CLOSE_SYNTAX, $chunk, 2);
                $column_request = $chunk_parts[0];
                $next_output    = $chunk_parts[1];

                if (array_key_exists($column_request, $column_indices)) {
                    $interpolated .= $row[$column_indices[$column_request]]->getDisplayValue();
                } else {
                    if ($this->getConf('show_not_found')) {
                        $renderer->cdata($this->getLang('none'));
                    }
                }
                $interpolated .= $next_output;
            }

            // Rendering needs an array to write
            $html_info = [];
            $html = p_render($mode, p_get_instructions($interpolated), $html_info);

            // Send to document
            $renderer->doc .= $html;
        }

        // Re-enable section editing
        $conf['maxseclevel'] = $old_maxseclevel;

        return true;
    }
}
