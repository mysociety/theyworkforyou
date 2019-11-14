# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://atlas.hashicorp.com/search.
  config.vm.box = "sagepe/stretch"

  # Enable NFS access to the disk
  config.vm.synced_folder ".", "/vagrant", disabled: true
  config.vm.synced_folder ".", "/vagrant/theyworkforyou"

  # NFS requires a host-only network
  # This also allows you to test via other devices (e.g. mobiles) on the same
  # network
  config.vm.network :private_network, ip: "10.11.12.13"

  # Give the VM a bit more power to speed things up
  config.vm.provider "virtualbox" do |v|
    v.memory = 2048
    v.cpus = 2
  end

  # Provision the vagrant box
  config.vm.provision "shell", inline: <<-SHELL
    export DEBIAN_FRONTEND=noninteractive
    echo 'deb http://mirror.bytemark.co.uk/debian stretch-backports main' > /etc/apt/sources.list.d/backports.list
    apt-get update

    chown vagrant:vagrant /vagrant
    cd /vagrant/theyworkforyou

    # Install needed things
    apt-get -qq -y install unzip fakeroot git libffi-dev >/dev/null

    # mysql and xapian
    bin/install-php7-xapian.sh
    bin/install-mysql vagrant

    # Install the packages from conf/packages
    grep -vE "^#" conf/packages | xargs apt-get install -qq -y >/dev/null

    # Install webserver
    apt-get -qq -y install apache2 libapache2-mod-php >/dev/null

    # apache config and modules
    cp conf/httpd.vagrant /etc/apache2/sites-enabled/twfy.conf
    a2enmod expires rewrite
    /etc/init.d/apache2 reload

    su vagrant -c 'bin/install-as-user vagrant 10.11.12.13 /vagrant yes'
    su vagrant -c 'bin/deploy.bash'

    echo "Your site should now be visible at http://10.11.12.13/"
  SHELL

  # It's likely the shared folder wasn't available when Apache started.
  # So restart Apache again once the machine has started up.
  config.vm.provision "shell", run: "always", inline: <<-SHELL
    sudo apachectl restart
    echo "Your site should now be visible at http://10.11.12.13/"
  SHELL

end
