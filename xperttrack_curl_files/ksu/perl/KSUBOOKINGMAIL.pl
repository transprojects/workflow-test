use DBI;
use DBD::mysql;
use DateTime;
use POSIX qw(strftime);
my $cdate = strftime "%Y\-%m\-%d", localtime;
my $ctime = strftime "%H\:%M\:%S", localtime;
my $time1,$time2,$function=("","","1");
$time1=$ARGV[1];
$time2=$ARGV[2];


##2024-01-30 17:05:51
#print "$cdate $ctime";
#exit;

my $workflow_dbh = DBI->connect('dbi:mysql:dbname=workflow;host=localhost','root', 'root@123') || print "Could not connect to database: $DBI::errstr";
$workflow_dbh->do( q{SET sql_mode = ''} );

if($ctime=~/^(0?9\:00\:00)$/)
{
TSPBOOKINGMAIL1();
}
if($ctime=~/^(14\:30\:00)$/)
{
TSPBOOKINGMAIL2();
}
if($ctime=~/^(18\:00\:00)$/)
{
TSPBOOKINGMAIL3();	
}
if($ctime=~/^(23\:59\:00)$/)
{
TSPBOOKINGMAIL4();	
}
if($function==1)
{
TSPBOOKINGMAIL();
}

$workflow_dbh->disconnect();

sub TSPBOOKINGMAIL1
{


		my $sql = $workflow_dbh->prepare("Select pub_id, cust_id,stage,status,created_dt from inw_inward_dtl where cust_id='42' and stage='FP' and status in('Pre-Clean','Tex-pre-clean') and created_dt between '".$cdate." 00:00:01' and '".$cdate." 09:00:00';");
		$sql->execute() or die "Unable to execute sql: $sql->errstr";
		$sql->rows;	

		
		my $article_namesc= "";	
		my $article_names= "";	
		my $article_list= "";	
		
		while(my $ref = $sql->fetchrow_hashref())
		{
			$article_names = $ref->{'pub_id'};
			$article_list.="<br>$article_names";
			$article_namesc++;
		}

		#print "$article_list\n";

			my $output = `php workflow-book.php "$article_list"`;
            
			sleep(3);
			exit;
		
}

sub TSPBOOKINGMAIL2
{


		my $sql = $workflow_dbh->prepare("Select pub_id, cust_id,stage,status,created_dt from inw_inward_dtl where cust_id='42' and stage='FP' and status in('Pre-Clean','Tex-pre-clean') and created_dt between '".$cdate." 09:00:00' and '".$cdate." 14:30:00';");
		$sql->execute() or die "Unable to execute sql: $sql->errstr";
		$sql->rows;	

		
		my $article_namesc= "";	
		my $article_names= "";	
		my $article_list= "";	
		
		while(my $ref = $sql->fetchrow_hashref())
		{
			$article_names = $ref->{'pub_id'};
			$article_list.="<br>$article_names";
			$article_namesc++;
		}

		#print "$article_list\n";

			my $output = `php workflow-book.php "$article_list"`;
            
			sleep(3);
			exit;
		
}

sub TSPBOOKINGMAIL3
{


		my $sql = $workflow_dbh->prepare("Select pub_id, cust_id,stage,status,created_dt from inw_inward_dtl where cust_id='42' and stage='FP' and status in('Pre-Clean','Tex-pre-clean') and created_dt between '".$cdate." 14:30:00' and '".$cdate." 18:00:00';");
		$sql->execute() or die "Unable to execute sql: $sql->errstr";
		$sql->rows;	

		
		my $article_namesc= "";	
		my $article_names= "";	
		my $article_list= "";	
		
		while(my $ref = $sql->fetchrow_hashref())
		{
			$article_names = $ref->{'pub_id'};
			$article_list.="<br>$article_names";
			$article_namesc++;
		}

		#print "$article_list\n";

			my $output = `php workflow-book.php "$article_list"`;
            
			sleep(3);
			exit;
		
}

sub TSPBOOKINGMAIL4
{


		my $sql = $workflow_dbh->prepare("Select pub_id, cust_id,stage,status,created_dt from inw_inward_dtl where cust_id='42' and stage='FP' and status in('Pre-Clean','Tex-pre-clean') and created_dt between '".$cdate." 18:00:00' and '".$cdate." 23:59:00';");
		$sql->execute() or die "Unable to execute sql: $sql->errstr";
		$sql->rows;	

		
		my $article_namesc= "";	
		my $article_names= "";	
		my $article_list= "";	
		
		while(my $ref = $sql->fetchrow_hashref())
		{
			$article_names = $ref->{'pub_id'};
			$article_list.="<br>$article_names";
			$article_namesc++;
		}

		#print "$article_list\n";

			my $output = `php workflow-book.php "$article_list"`;
            
			sleep(3);
			exit;
		
}

sub TSPBOOKINGMAIL
{


		my $sql = $workflow_dbh->prepare("Select pub_id, cust_id,stage,status,created_dt from inw_inward_dtl where cust_id='42' and stage='FP' and status in('Pre-Clean','Tex-pre-clean') and created_dt between '".$time1."' and '".$time2."';");
		$sql->execute() or die "Unable to execute sql: $sql->errstr";
		$sql->rows;	

		
		my $article_namesc= "";	
		my $article_names= "";	
		my $article_list= "";	
		
		while(my $ref = $sql->fetchrow_hashref())
		{
			$article_names = $ref->{'pub_id'};
			$article_list.="<br>$article_names";
			$article_namesc++;
		}

		#print "$article_list\n";

			my $output = `php workflow-book.php "$article_list"`;
            
			sleep(3);
			exit;
		
}


