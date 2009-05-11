#!/usr/bin/perl
# TWFYMail.pm:
# Code for TWFY incoming mail handling.
#
# Copyright (c) 2009 UK Citizens Online Democracy. All rights reserved.
# Email: louise@mysociety.org; WWW: http://www.mysociety.org
#
# $Id: TWFYMail.pm,v 1.1 2009-05-11 09:10:13 louise Exp $
#

package TWFYMail;

use strict;
require 5.8.0;

# Horrible boilerplate to set up appropriate library paths.
use DBI; 
use FindBin;
use lib "$FindBin::Bin/../perllib";
use lib "$FindBin::Bin/../../perllib";
use mySociety::Config;

BEGIN {
    mySociety::Config::set_file("$FindBin::Bin/../conf/general");
}

# Don't print diagnostics to standard error, as this can result in bounce
# messages being generated (only in response to non-bounce input, obviously).
mySociety::SystemMisc::log_to_stderr(0);

#-----------------------
=item get_bounced_address ADDRESS

Get the bounced address from a VERP address created with verp_envelope_sender

=cut
sub get_bounced_address($){
    my ($address) = @_;
    my $prefix = 'twfy-';
    my $domain =  mySociety::Config::get('EMAILDOMAIN');
    my $bounced_address =  mySociety::HandleMail::get_bounced_address($address, $prefix, $domain);
    return $bounced_address;
}

#----------------------
=item mark_as DESTINATION DATA

Log the email in the DATA hash in the DESTINATION file. 

=cut
sub mark_as($%){
    my ($destination, $data) = @_;
    mySociety::HandleMail::mark_as($destination, $data, mySociety::Config::get('MAIL_LOG_PREFIX'));
}
#----------------------
=item mark_deleted RECIPIENT DATA BOUNCED_ADDRESS

Mark the email address from BOUNCED_ADDRESS or RECIPIENT as having been deleted in the alerts table,
log the email in the DATA hash as having been deleted.

=cut
sub mark_deleted($$$){
    my ($recipient, $data, $bounced_address) = @_;
    my $email = $bounced_address || $recipient;
    if ($email){
        my $dsn = "DBI:mysql:" . mySociety::Config::get('DB_NAME') . ':' . mySociety::Config::get('DB_HOST');
        my $dbh = DBI->connect ($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASS'), { RaiseError => 1});
        $dbh->do('update alerts set deleted=1 where email = ?', {}, $email);
        mark_as('deleted', $data);
    }else{
        mark_as('unparsed', $data);
    }
}
#----------------------
=item handle_dsn_bounce ATTRIBUTES_REF DATA BOUNCED_ADDRESS

Handle a DSN bounce detailed in ATTRIBUTES_REF by either ignoring or deleting the email in the DATA hash. 

=cut 
sub handle_dsn_bounce($$$){
    my ($r, $data, $bounced_address) = @_;
    my %attributes = %{$r};
    my $status = $attributes{status};
    if ($status !~ /^5\./ || $status eq '5.2.2'){
        mark_as('ignored', $data);
    }else{
        mark_deleted($attributes{recipient}, $data, $bounced_address);
    }
}
#----------------------
=item handle_non_dsn_bounce ATTRIBUTES_REF DATA BOUNCED_ADDRESS
Handle a non-DSN bounce detailed in ATTRIBUTES_REF by either ignoring or deleting the email in the DATA hash, or 
marking it as unparsed.
=cut
sub handle_non_dsn_bounce($$$){
    my ($r, $data, $bounced_address) = @_;
    my %attributes = %{$r};
    if (!$attributes{problem}){
        mark_as('unparsed', $data);
        return;
    }
    my $err_type = mySociety::HandleMail::error_type($attributes{problem});
    if ($err_type == mySociety::HandleMail::ERR_TYPE_PERMANENT){
        mark_deleted($attributes{email_address}, $data, $bounced_address);
    }else{
        mark_as('ignored', $data);  
    }
}
#----------------------
=item handle_bounce DATA BOUNCED_ADDRESS

Handle an incoming bounce mail represented by the DATA hash. 
  
=cut
sub handle_bounce($$){
    my ($data, $bounced_address) = @_;
    my %data_hash = %{$data};
    my @lines = @{$data_hash{lines}};

    my %attributes = mySociety::HandleMail::parse_bounce(\@lines);
    if ($attributes{is_dsn}){
        handle_dsn_bounce(\%attributes, \%data_hash, $bounced_address);
    }else{
        handle_non_dsn_bounce(\%attributes, \%data_hash, $bounced_address);    
    }
}
#-----------------------
=item  handle_incoming DATA

Handle an incoming mail represented by the DATA hash. Extract a more reliable recipient 
address if possible. 

=cut
sub handle_incoming($){
    my ($data) = @_;
    my %data = %{$data};

    my $bounce_recipient = mySociety::HandleMail::get_bounce_recipient($data{message});
    my $bounced_address;
    if ($bounce_recipient){
        $bounced_address = get_bounced_address($bounce_recipient);
    }
    handle_bounce(\%data, $bounced_address);

}

1;
