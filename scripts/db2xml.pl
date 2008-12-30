#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;

# $Id: db2xml.pl,v 1.5 2008-12-30 18:42:55 angie Exp $
#
# compares DB content to XML data and updates xml
# 
# Magic numbers, and other properties of the destination schema
# are documented here:
#        http://parl.stand.org.uk/cgi-bin/moin.cgi/DataSchema
#        
# The XML files for Hansard objects come from the Public Whip parser:
#       http://scm.kforge.net/plugins/scmsvn/cgi-bin/viewcvs.cgi/trunk/parlparse/pyscraper/?root=ukparse
# And those for MPs are in (semi-)manually updated files here:
#       http://scm.kforge.net/plugins/scmsvn/cgi-bin/viewcvs.cgi/trunk/parlparse/members/?root=ukparse

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../../perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");


use Getopt::Long;
use DBI; 
use XML::Twig;

use vars qw($cronquiet $update_person $personid $members_xml_file $dbh $parldata $lastupdatedir $pwmembers $passer $debug
);

$parldata = mySociety::Config::get('RAWDATA');
$lastupdatedir = mySociety::Config::get('XAPIANDB') . '/../xml2db/';
$pwmembers = mySociety::Config::get('PWMEMBERS');

my $result = GetOptions (
                        "cronquiet" => \$cronquiet,
                        "update_person" => \$update_person,
                        "personid=s" => \$personid,
                        "debug" => \$debug,
                        );

#my $pwmembers = mySociety::Config::get('PWMEMBERS');
#$members_xml_file = $pwmembers . 'all-members.xml';

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
$dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASSWORD'), { RaiseError => 1, PrintError => 0 });


if ($update_person && $personid) {
    update_person();
}




sub update_person {

    $passer->{'file_updated'} = '';
    
    unless (db_load_person()) {
        die "couldn't load person";
        #$passer->{'person_db_vals'} now holds mps vals
    }
    #unless ($passer->{'person_db_vals'}->{'mp_website'}) {
    #    die "no url for person in database to update";
    #}
    $passer->{'person_in_file'} = 0;

    my $twig = new XML::Twig(
        twig_handlers => {
        'personinfo' => \&xml_load_person,
    }, output_filter => 'safe',
        pretty_print => 'record',
    );

    my $twigfile = ($pwmembers . "websites.xml");
    $twig->parsefile($twigfile);
    my $root = $twig->root ; 

    # the person may not have a record in the file at all yet
    unless ($passer->{'person_in_file'}) {
        my $newid = 'uk.org.publicwhip/person/' . $personid;
        my $elt= XML::Twig::Elt->new( personinfo => {
            'id' => $newid,
            'mp_website' => $passer->{'person_db_vals'}->{'mp_website'}
        }); 
        $elt->paste($root);
        $passer->{'file_updated'} = $pwmembers . "websites.xml";
        print "added person. ";
  #'<personinfo id="uk.org.publicwhip/person/11225" mp_website="http://www.sionsimonmp.org/"/>'

    }
    
    
    if ($passer->{'file_updated'} eq $twigfile) {
        print "writing out $twigfile" if $debug;
        open( FH, ">$twigfile") or die "cannot open $twigfile: $!";
        $twig->print( \*FH);
    }
    
    # should we read the file back in here to make sure it's really OK, 
    # or do we trust this code and XML::Twig?
    
    return 1;

}


sub xml_load_person {
    my ($twig, $mp) = @_;
    #$mp->print;
    my $personinfo_id = $mp->att('id');
    my $pid = '';
    if ($personinfo_id && $personinfo_id =~ m#uk.org.publicwhip/person/(\d+)$#) {
        $pid = $1;
        if ($pid == $personid) {
        $passer->{'person_in_file'} = 1;
            if ($mp->att('mp_website') eq $passer->{'person_db_vals'}->{'mp_website'}) {
                print "XML and DB already match, no update needed \n" if $debug;
                return 1;
            }
            unless ($passer->{'person_db_vals'}->{'mp_website'}) {
                # deleting record
                $mp->delete;
            }
            my $updated = $mp->set_att( mp_website => $passer->{'person_db_vals'}->{'mp_website'});
            if ($mp->att('mp_website') eq $passer->{'person_db_vals'}->{'mp_website'}) {
                $passer->{'file_updated'} = $pwmembers . "websites.xml";
                print "MP's URL updated \n" if $debug;
            }
        }
    }
    #$twig->purge();
    return 1;
}


sub db_load_person {
    my $person_db_vals = {};
    $passer->{'person_db_vals'} = {};
    my $get_person_sql = qq[SELECT member.person_id, house, title, first_name, last_name, constituency, data_value AS mp_website FROM personinfo LEFT JOIN member ON member.person_id =  personinfo.person_id WHERE data_key = 'mp_website' AND member.person_id = ] . $dbh->quote($personid);
    my $sth = $dbh->prepare($get_person_sql);
	$sth->execute();
		while (my $ref = $sth->fetchrow_hashref()) {
    	   foreach my $fieldname ( keys (%$ref) ) {
                $passer->{'person_db_vals'}->{$fieldname} = $ref->{$fieldname};
    	   }
		}
    if (!$passer->{'person_db_vals'}->{'person_id'}) {return 0;}
    return 1;
}

$dbh->disconnect();