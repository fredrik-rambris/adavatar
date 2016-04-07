<?php
/* This script goes in /var/www/web/avatar.example.com/ */
$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Could not connect");

$upn=$memcache->get($_GET["upn"]);
if(!$upn)
{
	header("HTTP/1.0 404 Not Found");
	echo "Not found";
	exit();
}

// using ldap bind
$ldaphost = "dc.example.com";
$ldaprdn  = 'EXAMPLE\user-with-read-access';     // ldap rdn or dn
$ldappass = 'secret-password-123';  // associated password

$ldapconn = ldap_connect($ldaphost)
	or die("Could not connect to LDAP server.");

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);

ldap_start_tls($ldapconn) or die("Could not start TLS\n");

// binding to ldap server
$ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass)
	or die("Could not login/bind to LDAP server.");

$result = ldap_search($ldapconn, 'ou=Users,ou=Company,dc=example,dc=com', "(&(&(objectclass=user)(objectcategory=person))(userPrincipalName=$upn))", array('thumbnailphoto'));

$size=isset($_GET["s"])?intval($_GET["s"]):64;
if ($result)
{
	foreach(ldap_get_entries($ldapconn, $result) as $user)
	{
		if(!is_array($user)) continue;
		header("Content-Type: image/jpeg");
		if(strlen($user["thumbnailphoto"][0]))
		{
			if(($im=imagecreatefromstring($user["thumbnailphoto"][0])))
			{
				$out=imagecreatetruecolor($size, $size);
				imagecopyresampled($out, $im, 0, 0, 0, 0, $size, $size, imagesx($im), imagesy($im));
				imagejpeg($out, null, 85);
			}
			else
			{
				echo $user["thumbnailphoto"][0];
			}
			exit;
		}
	}
}
/* Output default image if no thumbnail exists in AD */
$im=imagecreatefromjpeg("/var/www/web/avatar.example.com/default-avatar-96.jpg");
$out=imagecreatetruecolor($size, $size);
imagecopyresampled($out, $im, 0, 0, 0, 0, $size, $size, imagesx($im), imagesy($im));
imagejpeg($out, null, 85);
?>
