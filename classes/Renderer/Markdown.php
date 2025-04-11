<?php

namespace MySociety\TheyWorkForYou\Renderer;

/**
 * Markdown Renderer
 *
 * Use for converting markdown into help pages.
 */



class Markdown {
    public function markdown_document(string $this_page, bool $show_menu = true, array $extra_vars = []) {
        // This function takes a markdown file and converts it to HTML

        $markdown_file = '../../../markdown/' . $this_page . '.md';
        $Parsedown = new \Parsedown();

        $text = file_get_contents($markdown_file);
        $html = $Parsedown->text($text);

        # title is the first h1
        preg_match('/<h1>([^<]+)<\/h1>/i', $html, $matches);

        $title = $extra_vars['_page_title'] ?? $matches[1];

        $html = preg_replace_callback('/<h([1-3])>([^<]+)<\/h[1-3]>/i', function ($matches) {
            $level = $matches[1];
            $htitle = $matches[2];
            $slug = slugify($htitle);
            if ($level == 1) {
                $title_class = "js-toc-title";
            } else {
                $title_class = "js-toc-item";
            }
            return "<h$level id=\"$slug\" class=\"$title_class\">$htitle</h$level>";
        }, $html);

        // Create new panel when horizontal line used
        $html = preg_replace('/<hr \/>/i', '</div><div class="panel">', $html);

        foreach ($extra_vars as $key => $value) {
            $html = str_replace('{{ ' . $key . ' }}', $value, $html);
        };

        $context = [
            'html' => $html,
            'this_page' => $this_page,
            'page_title' => $title,
            'show_menu' => $show_menu,
            'og_image' => \MySociety\TheyWorkForYou\Url::generateSocialImageUrl($title),
        ];

        // Add extra variables to the context
        foreach ($extra_vars as $key => $value) {
            $context[$key] = $value;
        }

        \MySociety\TheyWorkForYou\Renderer::output('static/markdown_template', $context);

    }

    public static function render_php($template_file, array $context = []) {
        $path = INCLUDESPATH . 'easyparliament/templates/html/' . $template_file . '.php';
        extract($context, EXTR_SKIP);
        ob_start();
        include($path);
        return ob_get_clean();
    }
}
