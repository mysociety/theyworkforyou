# Developing using Docker

This page has an overview of the Docker-based development environment.

## Quickstart

You will need [a recent version of Docker](https://www.docker.com/products/docker-desktop).

Running `docker compose up [ -d ]` will start the environment. The first time you run this it may
take a few minutes as various images are downloaded and the application image built locally.

Once it's done, you should be able to view the front page at `http://localhost:8000`. However
the default build contains no data, so take a look at [INSTALL.md](INSTALL.md) for information
about downloading and importing Parlparse data (members, debates, votes, etc).

You can stop the environment by running `docker compose down`. Adding a `-v` will remove any
Docker volumes that may be in use, including all their data.

## Details

The application image created by the `Dockerfile` only contains the various dependencies
that TWFY needs to run, together with a web server capable of serving it. It is
`docker-compose.yml` that does the heavy lifting by bind mounting your local working copy of
the repository and running the `bin/docker-entrypoint.sh` script to start things up.

The first time the entrypoint script runs it will run both `composer` and `bundler` to set up
some dependencies. These will install various things into the `vendor/` directory in your
local filesystem, so subsequent runs will be faster. `vendor/` is currently a regular directory
in the filesystem included in the main bind mount, but may be converted to a Docker volume
at some point.

The system also creates another local directory structure , `data/` that is excluded from git.
This is intended to hold the data you will import into the environment. As this will need to
remain accessible to your local system, this should remain under the project's main bind mount.

The application container uses a Docker volume for its Xapian index, and the database container
one for its data directory.

## Additional Setup Tasks

The compose file should produce a functional environment but this will contain no real data.
To do any real work, you'll need to import at least a subset of data.

There are details in the [INSTALL.md](INSTALL.md); we'd suggest at least importing a few 
recent debates from https://www.theyworkforyou.com/pwdata/ and the people data from 
https://github.com/mysociety/parlparse. Once this is done, you'll also need to run the 
Xapian indexer for the first time for all the pages to work as expected.

## Running commands inside the application container

When the environment is running, you have a couple of options with regards to running commands
within the application container itself.

The first option is to start an interactive shell in the container, then run commands manually:

`docker compose exec twfy bash`

The second is to run each command via `docker compose exec` directly. These commands will run
inside the container relative to its `WORKDIR`, `/twfy/`, which is where the repository is bind
mounted to and output to your local shell for debugging.

This means you can do things like the example below.

### Managing Static Assets

Compile static assets (note that this is done when the container starts automatically, but you
may wish to run it manually as you make changes):

`docker compose exec twfy bundle exec compass compile ./www/docs/style`

Or watch the static assets and recompile automatically:

`docker compose exec twfy bundle exec compass watch ./www/docs/style`

### Importing and Indexing Data

These are commands you can run when loading data as described in [INSTALL.md](INSTALL.md).

To import some debates:

`docker compose exec twfy scripts/xml2db.pl --debates --all`

To load member data into the database:

`docker compose exec twfy scripts/load-people`

To build the Xapian index:

`docker compose exec twfy search/index.pl all`

### Development docker

The `docker-compose.dev.yml` variant includes additional variables and setup to run tests and xdebug locally. 

### Developing with VSCode

The `.devcontainer.json` will be default use the development docker. Open the repo in VSCode and relaunch the repo in the container to set up the development enviroment. 

### Developing on WSL

If running via WSL on windows, xdebug runs into issues because of confusion about where the various services are running (Windows docker exposes the IP address of windows host, which is where vscode client is running, but the vscode server is running on the WSL instance, and docker does not by default know about this). To deal with this, add `set-ip.sh` to `/etc/profile.d` in the WSL host:

```
export WSL_IP=$(hostname -I)
```

This will add the IP of the WSL host to an enviromental variable, which is then passed in by the `docker compose` file so xdebug (running on the docker) can communicate with the vscode server (running on WSL). This allows breakpoints to be used. 