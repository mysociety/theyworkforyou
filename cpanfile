# Runtime dependencies
requires 'DBI';
requires 'File::Slurp';
requires 'HTML::Entities';
requires 'HTML::Parser';
requires 'JSON';
requires 'LWP::Simple';
requires 'LWP::UserAgent';
requires 'Search::Xapian';
requires 'Text::CSV';
requires 'URI::Escape';
requires 'XML::RSS';
requires 'XML::Twig';

# Development/linting dependencies
on 'develop' => sub {
    requires 'Perl::Critic';
};
