<?php

namespace MySociety\TheyWorkForYou\Renderer;

/**
 * Template Footer Renderer
 *
 * Prepares variables for inclusion in a template, as well as handling variables
 * for use in header and footer.
 */

class Footer
{

    /**
     * Output Page
     *
     * Assembles the data required to display the page footer
     *
     * @param array  $data     An associative array of data to be made available to the template.
     */

    public function get_data($data = array())
    {
        global $DATA, $this_page, $THEUSER;

        $data['footer_links']['about'] = self::get_menu_links(array ('help', 'about', 'linktous', 'houserules', 'blog', 'news', 'contact', 'privacy'));
        $data['footer_links']['assemblies'] = self::get_menu_links(array ('hansard', 'sp_home', 'ni_home', 'wales_home', 'boundaries'));
        $data['footer_links']['international'] = self::get_menu_links(array ('newzealand', 'australia', 'ireland', 'mzalendo'));
        $data['footer_links']['tech'] = self::get_menu_links(array ('code', 'api', 'data', 'pombola', 'devmailinglist', 'irc'));

        return $data;
    }

    /**
     * Get Menu Links
     *
     * Takes an array of pages and returns an array suitable for use in links.
     */

    private static function get_menu_links($pages) {

        global $DATA, $this_page;
        $links = array();

        foreach ($pages as $page) {

            //get meta data
            $menu = $DATA->page_metadata($page, 'menu');
            if ($menu) {
                $title = $menu['text'];
            } else {
                $title = $DATA->page_metadata($page, 'title');
            }
            $url = $DATA->page_metadata($page, 'url');
            $tooltip = $DATA->page_metadata($page, 'heading');

            //check for external vs internal menu links
            if (!valid_url($url)) {
                $URL = new \URL($page);
                $url = $URL->generate();
            }

            //make the link
            if ($page == $this_page) {
                $links[] = array(
                    'href'    => '#',
                    'title'   => '',
                    'classes' => '',
                    'text'    => $title
                );
            } else {
                $links[] = array(
                    'href'    => $url,
                    'title'   => $tooltip,
                    'classes' => '',
                    'text'    => $title
                );
            }
        }

        return $links;
    }

}
