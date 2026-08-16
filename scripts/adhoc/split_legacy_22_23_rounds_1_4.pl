use strict;
use warnings;
use File::Path qw(make_path);
use Time::Piece;

my $src_dir = '/home/ned-bollard/TW3_PHP_Clone/reports/report_strays';
my $dst_dir = '/home/ned-bollard/TW3_PHP_Clone/reports/reports_v4_compat/22_23';

sub write_file {
  my ($path, $content) = @_;
  open my $out, '>', $path or die "Cannot write $path: $!";
  print {$out} $content;
  close $out;
}

opendir(my $dh, $src_dir) or die "Cannot open $src_dir: $!";
my @files = map { $src_dir . '/' . $_ }
  sort grep { /^Season 22_23  Round\s+\d+\.html$/ }
  readdir($dh);
closedir($dh);

die "No source files found in $src_dir\n" unless @files;

my $re_results = qr{(<h4[^>]*>\s*The\s*field:\s*</h4>.*?)(?=<h4[^>]*>\s*Handicap\s*Changes:\s*</h4>)}is;
my $re_results_fallback = qr{(<h4[^>]*>\s*The\s*field:\s*</h4>.*?)(?=<h4[^>]*>\s*Haggle\s*Movers:\s*</h4>)}is;
my $re_handicaps = qr{(<h4[^>]*>\s*Handicap\s*Changes:\s*</h4>.*?)(?=<h4[^>]*>\s*Haggle\s*Movers:\s*</h4>|<h4[^>]*>\s*Eclectic\s*Movers:\s*</h4>|</body>)}is;
my $re_best5 = qr{(<h4[^>]*>\s*Haggle\s*Movers:\s*</h4>.*?)(?=<h4[^>]*>\s*Eclectic\s*Movers:\s*</h4>)}is;
my $re_eclectic = qr{(<h4[^>]*>\s*Eclectic\s*Movers:\s*</h4>.*?)(?=<h4[^>]*>\s*The\s*Money\s*List:\s*</h4>|<h4[^>]*>\s*Ball\s*Baggers:\s*</h4>|</body>)}is;

for my $file (@files) {
  my ($round) = $file =~ /Round\s+(\d+)\.html$/i;
  next unless defined $round;

  open my $fh, '<', $file or die "Cannot read $file: $!";
  local $/;
  my $raw = <$fh>;
  close $fh;

  $raw =~ s/\r//g;

  my ($date_iso) = $raw =~ /Date:\s*([0-9]{4}-[0-9]{2}-[0-9]{2})/i;
  die "No date found in $file\n" unless $date_iso;

  my $tp = Time::Piece->strptime($date_iso, '%Y-%m-%d');
  my $slug = sprintf('%03d_%s', $round, $tp->strftime('%b_%d'));
  my $out_dir = $dst_dir . '/' . $slug;
  make_path($out_dir);

  my ($prefix) = $raw =~ /\A(.*?)(?=<h4\b)/is;
  $prefix = '' unless defined $prefix;

  my ($tail) = $raw =~ m{(</body>.*)\z}is;
  $tail = '</body></html>' unless defined $tail && $tail ne '';

  my ($results) = $raw =~ $re_results;
  if (!defined $results || $results eq '') {
    ($results) = $raw =~ $re_results_fallback;
  }

  my ($best5) = $raw =~ $re_best5;
  my ($handicaps) = $raw =~ $re_handicaps;
  my ($eclectic) = $raw =~ $re_eclectic;

  die "Could not extract results section from $file\n"
    unless defined $results && $results ne '';
  die "Could not extract best5 section from $file\n"
    unless defined $best5 && $best5 ne '';
  die "Could not extract eclectic section from $file\n"
    unless defined $eclectic && $eclectic ne '';

  $best5 =~ s/Haggle\s*Movers:/Best five rounds to date:/i;
  $eclectic =~ s/Eclectic\s*Movers:/Eclectic scores to date:/i;

  write_file($out_dir . '/10_Results.html', $prefix . $results . $tail);
  write_file($out_dir . '/31_Best_5_Scores.html', $prefix . $best5 . $tail);
  if (defined $handicaps && $handicaps ne '') {
    write_file($out_dir . '/61_Handicaps.html', $prefix . $handicaps . $tail);
  }
  write_file($out_dir . '/41_Eclectic_Legacy.html', $prefix . $eclectic . $tail);
  write_file($out_dir . '/49_Eclectic_Legacy.html', $prefix . $eclectic . $tail);
  write_file($out_dir . '/49_Eclectic_Twilight.html', $prefix . $eclectic . $tail);

  print "Created $out_dir\n";
}

print "Done.\n";
