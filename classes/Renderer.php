<?php

namespace MySociety\TheyWorkForYou;

/**
 * Template Renderer
 *
 * Prepares variables for inclusion in a template, as well as handling variables
 * for use in header and footer.
 */

class Renderer
{

    /**
     * Output Page
     *
     * Assembles a completed page from template and sends it to output.
     *
     * @param string $template The name of the template file to load.
     * @param array  $data     An associative array of data to be made available to the template.
     */

    public static function output($template, $data = array())
    {

        global $page_errors;
        ////////////////////////////////////////////////////////////
        // Find the user's country. Used by header, so a safe bit to do regardless.
        if (preg_match('#^[A-Z]{2}$#i', get_http_var('country'))) {
            $data['country'] = strtoupper(get_http_var('country'));
        } else {
            $data['country'] = Utility\Gaze::getCountryByIp($_SERVER["REMOTE_ADDR"]);
        }

        ////////////////////////////////////////////////////////////
        // Get the page data
        global $DATA, $this_page, $THEUSER;

        $header = new Renderer\Header();
        $data = array_merge($header->data, $data);

        $user = new Renderer\User();
        $data = array_merge($user->data, $data);

        if ( isset($page_errors) ) {
            $data['page_errors'] = $page_errors;
        }

        ////////////////////////////////////////////////////////////
        // Search URL

        $SEARCH = new \URL('search');
        $SEARCH->reset();
        $data['search_url'] = $SEARCH->generate();

        ////////////////////////////////////////////////////////////
        // Search URL
        // Footer Links

        $footer = new Renderer\Footer();
        $data['footer_links'] = $footer->data;

        # banner text
        $b = new Model\Banner;
        $data['banner_text'] = $b->get_text();

        $data = self::addCommonURLs($data);

        # mini survey
        // we never want to display this on the front page or any
        // other survey page we might have
        if (!in_array($this_page, array('survey', 'overview'))) {
            $mini = new MiniSurvey;
            $data['mini_survey'] = $mini->get_values();
        }

        ////////////////////////////////////////////////////////////
        // Unpack the data we've been passed so it's available for use in the templates.

        extract($data);

        ////////////////////////////////////////////////////////////
        // Require the templates and output

        header('Content-Type: text/html; charset=iso-8859-1');
        require_once INCLUDESPATH . 'easyparliament/templates/html/header.php';
        require_once INCLUDESPATH . 'easyparliament/templates/html/' . $template . '.php';
        require_once INCLUDESPATH . 'easyparliament/templates/html/footer.php';
    }

    private static function addCommonURLs($data) {
        $urls = array();
        if ( isset($data['urls']) ) {
            $urls = $data['urls'];
        }

        $common_urls = array('search', 'alert');

        foreach ( $common_urls as $path ) {
            if (!isset($urls[$path]) ) {
                $url = new \URL($path);
                $urls[$path] = $url->generate();
            }
        }

        $data['urls'] = $urls;
        return $data;
    }

}
