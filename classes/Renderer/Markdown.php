<?php

namespace MySociety\TheyWorkForYou\Renderer;

/**
 * Markdown Renderer
 *
 * Use for converting markdown into help pages.
 */

class Markdown
{
    public function markdown_document($this_page){
        // This function takes a markdown file and converts it to HTML
    
        $markdown_file = '../../../markdown/' . $this_page . '.md';
        $Parsedown = new \Parsedown();
        
        $text = file_get_contents($markdown_file);
        $html = $Parsedown->text($text);
        
        # title is the first h1
        preg_match('/<h1>([^<]+)<\/h1>/i', $html, $matches);
        
        $title = $matches[1];
        
        $html = preg_replace_callback('/<h([1-3])>([^<]+)<\/h[1-3]>/i', function($matches) {
            $level = $matches[1];
            $htitle = $matches[2];
            $slug = slugify($htitle);
            return "<h$level id=\"$slug\" class=\"js-toc-item\">$htitle</h$level>";
        }, $html);
        
        
        \MySociety\TheyWorkForYou\Renderer::output('static/markdown_template', array(
            'html' => $html,
            'this_page' => $this_page,
            'page_title' => $title,
        ));
        
    }
}
