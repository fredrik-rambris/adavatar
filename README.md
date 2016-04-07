# adavatar
A gravatar-like interface for Active Directory user thumbnails

Serves photos from Active Directory (LDAP attribute thumbnailphoto) with
an interface like Gravatar.

Requires memcached, php with LDAP support

Memcached is used to reduce LDAP searches based on userPrincipalName. I was
paranoid and added a memcached that gets updated every so often.

## Setup

Updated LDAP connect info and search base

Put adavatar.php into /etc/cron.hourly
Put avatar.example.com.conf into your Apache httpd config, adjust accodingly
Put avatar.php in the DocumentRoot you specified in your VirtualHost

