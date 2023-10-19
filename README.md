# Finance Tracker

Track finance data (stocks and crypto currencies).

[![DockerHub](https://img.shields.io/badge/download-DockerHub-blue?logo=docker)](https://hub.docker.com/r/programie/financetracker)
[![GitHub release](https://img.shields.io/github/v/release/Programie/FinanceTracker)](https://github.com/Programie/FinanceTracker/releases/latest)

## Installation

### Docker (recommended)

Have a look at the [docker-compose.sample.yml](docker-compose.sample.yml) to see how to set up the application using Docker.

Create a new database and import the [database.sql](database.sql) into it.

### Manual

For the manual installation method, you need a webserver running at least PHP 8.0.

Download the [latest release](https://github.com/Programie/FinanceTracker/releases/latest) and extract it onto your webserver. Configure the document root to point to the `httpdocs` directory.

As of now, it is only possible to use environment variables to configure the application. Have a look into the [docker-compose.sample.yml](docker-compose.sample.yml) for a list of all environment variables.

Create a new database and import the [database.sql](database.sql) into it.