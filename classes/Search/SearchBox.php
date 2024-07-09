<?php

namespace MySociety\TheyWorkForYou\Search;


class SearchBox
{
    public string $homepage_panel_class;
    public string $homepage_subhead;
    public string $homepage_desc;
    public string $search_section;
    public array $quick_links;

    public function __construct(string $homepage_panel_class = '',
                                string $homepage_subhead = '',
                                string $homepage_desc = '',
                                string $search_section = '',
                                array $quick_links = [])
    {
        $this->homepage_panel_class = $homepage_panel_class;
        $this->homepage_subhead = $homepage_subhead;
        $this->homepage_desc = $homepage_desc;
        $this->search_section = $search_section;
        $this->quick_links = $quick_links;
    }

    public function add_quick_link(string $title, string $url): void
    {
        $this->quick_links[] = ['title' => $title, 'url' => $url];
    }

}

?>