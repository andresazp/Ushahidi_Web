define bulkpackage($packages) {
    $packages_join = inline_template('<% packages.each do |package| %><%= package %> <% end %>')
    exec { "apt-get -y install $packages_join && apt-get clean":
        onlyif  => "dpkg-query -W -f='\${Status}\\n' $packages_join 2>&1 |grep -v installed",
        path    => "/bin:/sbin:/usr/bin:/usr/sbin",
        timeout => 7200,
    }
}

Exec {
    path => "/usr/sbin:/usr/bin:/sbin:/bin",
}

group { "puppet":
  ensure => "present",
}
File { owner => 0, group => 0, mode => 0644 }

file { '/etc/motd':
  content => "Welcome to your Vagrant-built virtual machine!
    Managed by Puppet.\n"
}

exec { "apt-get_update":
    command     => "/usr/bin/apt-get update",
    require     => [ File["norecommends"],
                     File["defaultrelease"],
                   ],
    refreshonly => true,
}

exec { "apt-get_upgrade":
    command     => "/usr/bin/apt-get upgrade",
    require     => [ Exec["apt-get_update"] ],
    refreshonly => true,
}

file { "norecommends":
    path    => "/etc/apt/apt.conf.d/02norecommends",
    content => "APT::Install-Recommends \"0\";",
}

file { "defaultrelease":
    path    => "/etc/apt/apt.conf.d/03defaultrelease",
    content => "APT::Default-Release \"oneiric\";",
}

$web_packages = [
    "apache2",
    "php5",
    "php5-mysql",
    "php5-mcrypt",
    "php5-curl",
    "php5-imap",
    "php5-xcache",
    "php5-gd",
    "php5-xdebug",
    "php-pear",
    "mysql-client",
    "mysql-server",
    "curl",
    "wget",
    "git",
    "postfix",
    "byobu",
]

Package {
    ensure  => installed,
    require => Bulkpackage["web-packages"],
}

bulkpackage { "web-packages":
    packages => $web_packages,
    require  => [ Exec["apt-get_update"],
                  Exec["apt-get_upgrade"]
                ],
}

package { $web_packages: }

# apache2
service { "apache2":
  ensure  => running,
  require => Package["apache2"],
}
exec { "apache2-reload":
  command     => "service apache2 reload",
  refreshonly => true,
}
exec { "apache2-mod-rewrite":
  command     => "a2enmod rewrite",
  notify => [Exec["apache2-reload"], ],
}

exec { "apache2-dissite-default":
  command     => "a2dissite default",
  notify => [Exec["apache2-reload"], ],
}
exec { "apache2-ensite-ushahidi":
  command     => "a2ensite ushahidi",
  notify => [Exec["apache2-reload"], ],
  require => [File["/etc/apache2/sites-available/ushahidi"]]
}
file { "/etc/apache2/sites-available/ushahidi":
        ensure  => "present",
        owner   => "root",
        group   => "root",
        mode    => 644,
        content => template("ushahidi.erb"),
        require => Package["apache2"],
        notify  => Service["apache2"], # TODO check this causes a reload only if changed
}


}
