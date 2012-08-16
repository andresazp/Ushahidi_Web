# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant::Config.run do |config|
  config.vm.box = "ubuntu-1110-server-amd64"
  # config.vm.box_url = "http://domain.com/path/to/above.box"
  config.vm.customize ["modifyvm", :id, "--memory", "512"]
  config.vm.network :hostonly, "192.168.33.11"
  config.ssh.port = 2201
  config.vm.forward_port 22, 2201
  config.vm.share_folder "www", "/var/www", "/Users/robbie/www/ushahidi_car", :nfs => true

  # Enable provisioning with Puppet stand alone.  Puppet manifests
  # are contained in a directory path relative to this Vagrantfile.
  config.vm.provision :puppet do |puppet|
    puppet.manifests_path = "puppet/manifests"
    puppet.manifest_file  = "ubuntu-1110-server-amd64.pp"
  end
end
