# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://atlas.hashicorp.com/search.
  config.vm.box = "ubuntu/trusty64"

  # Enable NFS access to the disk
  config.vm.synced_folder ".", "/vagrant", disabled: true
  config.vm.synced_folder ".", "/vagrant/theyworkforyou", :nfs => true

  # NFS requires a host-only network
  # This also allows you to test via other devices (e.g. mobiles) on the same
  # network
  config.vm.network :private_network, ip: "10.11.12.13"

  # Django dev server
  config.vm.network "forwarded_port", guest: 8000, host: 8000

  # Give the VM a bit more power to speed things up
  config.vm.provider "virtualbox" do |v|
    v.memory = 2048
    v.cpus = 2
  end

  # Provision the vagrant box
  config.vm.provision "shell", inline: <<-SHELL
    apt-get update

    chown vagrant:vagrant /vagrant
    cd /vagrant/theyworkforyou

    # Install the packages from conf/packages.ubuntu-trusty
    grep -vE "^#" conf/packages | xargs apt-get install -qq -y

    # Run install-as-user to set up a database, virtualenv, python, sass etc
    apt-get -qq -y install apache2 libapache2-mod-php5 ruby-compass ruby-bundler php5-curl php5-mysql php5-memcache memcachedb

    # set memcache back to the standard port
    sed -i -e 's!-p 21201!-p 11211!' /etc/memcachedb.conf
    service memcachedb restart

    # apache config and modules
    cp conf/httpd.vagrant /etc/apache2/sites-enabled/twfy.conf
    a2enmod expires rewrite php5
    /etc/init.d/apache2 reload

    # mysql and xapian
    bin/install-mysql vagrant
    bin/install-php5-xapian.sh

    su vagrant -c 'bin/install-as-user vagrant 10.11.12.13 /vagrant yes'
    su vagrant -c 'bin/deploy.bash'

    echo "Your site should now be visible at http://10.11.12.13/"
  SHELL

end
