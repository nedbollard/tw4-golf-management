use strict;
use warnings;
use File::Find;

my $base = '/home/ned-bollard/TW3_PHP_Clone/reports/reports_v4_compat/22_23';
my %target_round = map { $_ => 1 } qw(001_Sep_28 002_Oct_12 003_Oct_20 004_Nov_02);

sub read_file {
  my ($path) = @_;
  open my $fh, '<', $path or die "Cannot read $path: $!";
  local $/;
  my $txt = <$fh>;
  close $fh;
  return $txt;
}

sub write_file {
  my ($path, $txt) = @_;
  open my $fh, '>', $path or die "Cannot write $path: $!";
  print {$fh} $txt;
  close $fh;
}

sub title_for_file {
  my ($path) = @_;
  return 'Ohariu Valley Twilight Golf' if $path =~ m{/31_Best_5_Scores\.html$}i;
  return 'Ohariu Valley Twilight Golf' if $path =~ m{/41_Eclectic_}i;
  return 'Ohariu Valley Twilight Golf' if $path =~ m{/49_Eclectic_}i;
  return 'Ohariu Valley Golf Club Round Summary';
}

sub normalize_html {
  my ($html, $title) = @_;

  $html =~ s/\r//g;
  $html =~ s/<!--\s*saved from url=.*?-->\s*//igs;

  my $body = $html;
  if ($html =~ m{<body[^>]*>(.*)</body>}is) {
    $body = $1;
  }

  # Idempotence: if this script is rerun, collapse previously-added wrappers,
  # including malformed cases where opening/closing counts drifted.
  $body =~ s{\A(?:\s*<div class="container-fluid">\s*)+}{}is;
  $body =~ s{(?:\s*</div>\s*)+\z}{}is;

  $body =~ s{</?pre>}{}ig;
  $body =~ s{\s+align="[^"]*"}{}ig;
  $body =~ s{\s+cellpadding="[^"]*"}{}ig;
  $body =~ s{\s+cellspacing="[^"]*"}{}ig;
  $body =~ s{\s+style="[^"]*"}{}ig;
  $body =~ s{<tbody>}{}ig;
  $body =~ s{</tbody>}{}ig;

  $body =~ s{<table\b[^>]*>}{<table class="table table table-striped table-bordered table-hover table-condensed">}ig;

  my $head = '<!DOCTYPE html><HTML lang="en"><HEAD><TITLE>' . $title
    . '</TITLE><link rel="stylesheet" href = "https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css"><script src = "https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>H4{line-height: 2; color: green}</style></HEAD>';

  return $head . '<BODY><div class="container-fluid">' . $body . '</div></BODY></HTML>';
}

my @files;
for my $round (sort keys %target_round) {
  my $dir = "$base/$round";
  next unless -d $dir;
  find(
    sub {
      return unless -f $_;
      return unless $_ =~ /\.html$/i;
      push @files, $File::Find::name;
    },
    $dir
  );
}

for my $path (sort @files) {
  my $src = read_file($path);
  my $title = title_for_file($path);
  my $dst = normalize_html($src, $title);
  write_file($path, $dst);
  print "Normalized $path\n";
}

print "Done.\n";
