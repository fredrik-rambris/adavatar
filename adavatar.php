#!/usr/bin/php
<?php
/**
 * Updates memcache with users from LDAP. Run from cron every so often
 */

$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Could not connect");

foreach(getUsers() as $hash=>$upn)
{
	$memcache->set($hash, $upn);
}

function getUsers()
{
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

	$users=array();

	// enable pagination with a page size of 100.
	$pageSize = 500;

	$cookie = '';
	do {
		ldap_control_paged_result($ldapconn, $pageSize, true, $cookie);

		$result = ldap_search($ldapconn, 'ou=Users,ou=Company,dc=example,dc=com', "(&(objectclass=user)(objectcategory=person))", array('mail', 'userPrincipalName'));
		if ($result)
		{
			foreach(ldap_get_entries($ldapconn, $result) as $user)
			{
				if(!is_array($user)) continue;
				$u=array();
				foreach($user as $key=>$val)
				{
					if(is_array($val)) $u[$key]=strtolower($val[0]);
				}
				if(!isset($u["userprincipalname"])) continue;
				if(!isset($u["mail"])) continue;
				$users[md5($u["userprincipalname"])]=$u["userprincipalname"];
				if(strcmp($u["userprincipalname"], $u["mail"]))
				{
					$users[md5($u["mail"])]=$u["userprincipalname"];
				}
			}
		}
		ldap_control_paged_result_response($ldapconn, $result, $cookie);

	} while($cookie !== null && $cookie != '');

	return $users;
}
?>
