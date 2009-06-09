#!/usr/bin/perl -w

my $file = shift;
open(FP, $file) or die $!;
my $in;
read(FP, $in, 9); # FLV header
$nothere=0;
while (!eof(FP)) {
    my $prevsize;
    read(FP, $prevsize, 4);
    read(FP, $tag, 1);
    $tag = ord($tag);
    read(FP, $datasize, 3);
    $datasize = ui24($datasize);
    read(FP, $timestamp, 3);
    $timestamp = ui24($timestamp);
    read(FP, $timestamp_ex, 1);
    $timestamp_ex = ord($timestamp_ex);
    read(FP, $streamID, 3);
    if (ord($streamID)!=0) {
        print "PROBLEM!"; exit;
    }
    seek(FP, $datasize, 1);
    next if $timestamp<278*60*1000 && $nothere==0;
    print "DataLen=$datasize, tag=$tag, timestamp=$timestamp, ex=$timestamp_ex\n";
    $nothere=1;
}

sub ui24 {
    my $n = shift;
    return (ord(substr($n,0,1))<<16) + (ord(substr($n,1,1))<<8) + ord(substr($n,2,1));
}
