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
 * ubikc module renderer
 *
 * @package    mod
 * @subpackage ubikc
 * @copyright  2012 HsiHo Huang  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class mod_ubikc_renderer extends plugin_renderer_base {

    /**
     * Prints file ubikc tree view
     * @param object $ubikc instance
     * @param object $cm instance
     * @param object $course
     * @return void
     */
    public function ubikc_tree($ubikc, $cm, $course) {
        $this->render(new ubikc_tree($ubikc, $cm, $course));
    }

    public function render_ubikc_tree(ubikc_tree $tree) {
        global $PAGE;

        echo '<div id="ubikc_tree">';
        echo $this->htmllize_tree($tree, $tree->dir);
        echo '</div>';
        $this->page->requires->js_init_call('M.mod_ubikc.init_tree', array(true));
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     */
    protected function htmllize_tree($tree, $dir) {
        global $CFG;

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $result .= '<li>'.s($subdir['dirname']).' '.$this->htmllize_tree($tree, $subdir).'</li>';
        }
        foreach ($dir['files'] as $file) {
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$tree->context->id.'/mod_ubikc/content/'.$tree->ubikc->kcrevision.$file->get_filepath().$file->get_filename(), true);
            $filename = $file->get_filename();
            $result .= '<li><span>'.html_writer::link($url, $filename).'</span></li>';
        }
        $result .= '</ul>';

        return $result;
    }
}

class ubikc_tree implements renderable {
    public $context;
    public $ubikc;
    public $cm;
    public $course;
    public $dir;

    public function __construct($ubikc, $cm, $course) {
        $this->ubikc = $ubikc;
        $this->cm     = $cm;
        $this->course = $course;

        $this->context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'mod_ubikc', 'content', 0);
    }
}