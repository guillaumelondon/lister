<?php

/*

LISTER - QUICK START :

configure file  : lister_config.php
Then configure telegram API ending (look for web.telegram.org)
to run manually : php lister_db.php

Put crontabs to run it automatically, to refresh it time to tmie and to clean it :

Examples of cronjobs to add on server so that lister works nicely

5 4 * * *       root /usr/bin/php /root/lister_db.php

0 0 1 * *       root rm -f /root/lister/found.*

5 5 1 * 2       root rm -f /root/lister/information.json;rm -f /root/lister/db/*

*/

date_default_timezone_set ('GMT');

// By g 2021 - will check if someone inserts files we do not want.

include_once("lister_config.php");

if($run==0) {
echo "TO CONFIGURE : PLEASE EDIT lister_config.php, put the right information and then put the run variable to 1\n";
die();
}

$found = Array ();              // whatever was found is empty

if (!file_exists ($install_dir))
  {
    mkdir ($install_dir, 0777, true);
  }

$tocompare = 0;

if (file_exists ($install_dir.'/information.json'))
  {
    $tocompare = 1;
  }


function _syslog($subject,$info)
{
 openlog("lister_db", LOG_PID | LOG_PERROR, LOG_LOCAL0);
 syslog(LOG_WARNING, $subject." ".$info );
 closelog();
}

function alerting ($string)
{
 $url = "https://web.telegram.org?putyourbotinformation?message=".$string;     // target for sending alert
 file_get_contents ("$url");
}

function
send_array ()
{
  global $found, $subject;

  $i = 0;
  $info = "";
  foreach ($found as $val)
  {
    $i++;
    $info .= $val."\n";

    if ($i > 5)
      {
        alerting(urlencode ($subject)."&m=".urlencode ($info));
        _syslog($subject,$info);
        $i = 0;
        $info = "";
      }


  }

  if (strlen ($info) > 0)
    {
      alerting(urlencode ($subject)."&m=".urlencode ($info));
      _syslog($subject,$info);
    }


}

function
insert_db ($ff, $date)
{
  global $found;
  global $tocompare;
  global $install_dir;
  global $mode;

  $d = "db";

  if (!file_exists ($install_dir."/$d"))
    {
      mkdir ($install_dir."/$d", 0777, true);
    }


  if(file_exists($ff)) {
        $fz = filesize($ff);
  } else $fz = 0;

  $file = $install_dir."/$d/db".md5 ($ff);
  if (!file_exists ($file))
    {
      $fp = fopen ($file, "w");
      fputs ($fp, $ff." ".$fz."\n");
      fclose ($fp);
      if ($tocompare == 1)
        {
          array_push ($found, "$ff $date (new) ($fz)");
        }
    }
  else
    {

      $info = file_get_contents ($file);
      if ($info != $ff." ".$fz."\n")
        {
          if($mode==1) {
            array_push ($found, "$ff $date (mod) ($fz)");
            $fp = fopen ($file, "w");
            fputs ($fp, $ff." ".$date."\n");
            fclose ($fp);
          }

        }

    }


// acl for .php is in function - no need of check() anymore

function
getDirContents ($dir, &$results = array ())
{
  global $target_dir;

  $pp = popen ('find '.$target_dir.' -type f -name "*.php"', "r");

  while (!feof ($pp))
    {

      $path = str_replace ("\n", "", fgets ($pp, 4096));
      insert_db ($path, filemtime ($path));
    }

  fclose ($pp);

}


$results = getDirContents ($target_dir);


if ($tocompare == 1)
  {
    if (!empty ($found))
      {
        file_put_contents ($install_dir."/found.".date ("Ymd")."_".
                           date ("His"), print_r ($found, 1));
        send_array ();
      }

  }

if ($tocompare == 1)
  {
  }

file_put_contents ($install_dir."/information.json", date ("Y/m/d H:i:s."));

?>
