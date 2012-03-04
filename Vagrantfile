# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant::Config.run do |config|
  config.vm.box = "ubuntu-1110-server-amd64"
  # config.vm.box_url = "http://domain.com/path/to/above.box"
  # config.vm.customize ["modifyvm", :id, "--memory", "1024"]
  config.vm.network :hostonly, "192.168.33.11"
  # config.vm.network :bridged
  # config.vm.forward_port 80, 8080
  config.vm.share_folder "www", "/var/www", "/Users/robbie/www/ushahidi_car"
  # Enable provisioning with Puppet stand alone.  Puppet manifests
  # are contained in a directory path relative to this Vagrantfile.
  # You will need to create the manifests directory and a manifest in
  # the file ubuntu-1110-server-amd64.pp in the manifests_path directory.
  #
  # An example Puppet manifest to provision the message of the day:
  #
  # # group { "puppet":
  # #   ensure => "present",
  # # }
  # #
  # # File { owner => 0, group => 0, mode => 0644 }
  # #
  # # file { '/etc/motd':
  # #   content => "Welcome to your Vagrant-built virtual machine!
  # #               Managed by Puppet.\n"
  # # }
  #
  config.vm.provision :puppet do |puppet|
    puppet.manifests_path = "puppet/manifests"
    puppet.manifest_file  = "ubuntu-1110-server-amd64.pp"
  end
end
