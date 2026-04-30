<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Optional outgoing URL rewriter for menuname-based viewer links.
 *
 * @package    local_page
 * @copyright  2025 Marcin Czaja RoseaThemes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_page;

/**
 * Rewrites {@see moodle_url} output from /local/page/index.php?menuname=slug to /slug (under the site wwwroot path).
 *
 * Moodle calls this only when {@code $CFG->urlrewriteclass} is set to {@see \local_page\url_rewriter} in config.php.
 * It does not accept incoming HTTP requests: the web server must still map /slug to Moodle (see README.md).
 */
class url_rewriter implements \core\output\url_rewriter {
    /**
     * Rewrite local page menuname viewer URLs to root-level slugs.
     *
     * @param \moodle_url $url URL that may point at the local page viewer
     * @return \moodle_url Rewritten URL or the original if not applicable
     */
    public static function url_rewrite(\moodle_url $url): \moodle_url {
        global $CFG;

        if (isset($CFG->upgraderunning)) {
            return $url;
        }

        $path = $url->get_path(false);
        if (!str_ends_with($path, '/local/page/index.php')) {
            return $url;
        }

        $params = $url->params();
        if (empty($params['menuname']) || !is_string($params['menuname']) || !empty($params['id'])) {
            return $url;
        }

        $menuname = clean_param($params['menuname'], PARAM_ALPHANUMEXT);
        if ($menuname === '' || $menuname !== $params['menuname']) {
            return $url;
        }

        return new \moodle_url('/' . $menuname);
    }

    /**
     * Hook for additional head setup; no extra output for this rewriter.
     */
    public static function html_head_setup(): void {
    }
}
