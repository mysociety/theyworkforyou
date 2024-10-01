<?php

namespace MySociety\TheyWorkForYou\Renderer;

/**
 * Template Footer Renderer
 *
 * Prepares variables for inclusion in a template, as well as handling variables
 * for use in header and footer.
 */

class Footer {
    public $data;

    private $about_links =  ['help', 'about', 'linktous', 'news', 'privacy'];
    private $assemblies_links = ['hansard', 'sp_home', 'wales_home', 'ni_home', 'london_home'];
    private $international_links = ['australia', 'ireland', 'mzalendo'];
    private $tech_links = ['code', 'api', 'data', 'devmailinglist', 'contact'];


    /*
     * Constructor
     *
     * Populates $data with array of array of links in the footer
     */
    public function __construct() {
        $this->data = [];

        $this->data['about'] = $this->get_menu_links($this->about_links);
        $this->data['assemblies'] = $this->get_menu_links($this->assemblies_links);
        $this->data['international'] = $this->get_menu_links($this->international_links);
        $this->data['tech'] = $this->get_menu_links($this->tech_links);
    }

    /**
     * Get Menu Links
     *
     * Takes an array of pages and returns an array suitable for use in links.
     */

    private function get_menu_links($pages) {

        global $DATA, $this_page;
        $links = [];

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
                $URL = new \MySociety\TheyWorkForYou\Url($page);
                $url = $URL->generate();
            }

            //make the link
            if ($page == $this_page) {
                $links[] = [
                    'href'    => '#',
                    'title'   => '',
                    'classes' => '',
                    'text'    => $title,
                ];
            } else {
                $links[] = [
                    'href'    => $url,
                    'title'   => $tooltip,
                    'classes' => '',
                    'text'    => $title,
                ];
            }
        }

        return $links;
    }

}
