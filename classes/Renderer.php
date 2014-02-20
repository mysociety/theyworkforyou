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
     */

    public static function output($template, $data = array())
    {

        // Find the user's country
        if (get_http_var('country')) {
            $country = strtoupper(get_http_var('country'));
        } else {
            $country = Gaze::get_country_by_ip($_SERVER["REMOTE_ADDR"]);
        }

        // Unpack the data we've been passed so it's available for use in the templates.
        extract($data);

        // Header
        require_once INCLUDESPATH . 'easyparliament/templates/html/header.php';

        // Page content
        require_once INCLUDESPATH . 'easyparliament/templates/html/' . $template . '.php';

        // Footer
        require_once INCLUDESPATH . 'easyparliament/templates/html/footer.php';
    }

}
