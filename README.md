git-user-bend
================
![Test](https://github.com/raphaelstolt/git-user-bend/workflows/test/badge.svg) [![Version](http://img.shields.io/packagist/v/stolt/git-user-bend.svg?style=flat)](https://packagist.org/packages/stolt/git-user-bend)
![PHP Version](http://img.shields.io/badge/php-8.0+-ff69b4.svg)

The `git-user-bend` CLI is a utility tool for managing the Git user configuration (i.e. name and email) of a given Git repository. With it you can create a set of __personas__ and easily __bend__ them when doing company work, open source development, or __pair__ programming sessions.

With the in Git `2.13` introduced [conditional configuration includes](https://git-scm.com/docs/git-config#_conditional_includes) you can set a `.gitconfig` for a group of repositories, which already might suit your needs.

#### Known relatives
This CLI is influenced by the [Gas](https://github.com/walle/gas) Ruby gem and might also contain elements of [pair](https://github.com/square/pair).

#### Installation
The `git-user-bend` CLI should be installed globally through Composer.

``` bash
composer global require stolt/git-user-bend
```

Make sure that the path to your global vendor binaries directory is in your `$PATH`. You can determine the location of your global vendor binaries directory via `composer global config bin-dir --absolute`. This way the `git-user-bend` executable can be located.

Since the default name of the CLI is quite a mouthful, an alias which can be placed in `~/.aliases`, `~/.zshrc` or the like might come in handy. The alias shown next assumes that `$COMPOSER_HOME` is `~/.config/composer` and not `~/.composer`.

``` bash
alias gub='~/.config/composer/vendor/bin/git-user-bend $@'
```

## Usage
Run the `git-user-bend whoami` CLI within a Git repository (or an argumented one) and it will allow you to view the currently used persona respectively the Git user configuration details.

The personas and their usage frequencies are stored in a JSON based global storage file called `.gub.personas` in the `$HOME` directory of your system.

Via a `.gub` dotfile it's also possible to add the details of __single__ persona directly into a project repository. This is mostly oriented for repository maintainers working on multiple machines and with multiple personas. To keep your email address __private__ consider using your `username@users.noreply.github.com` email address, for details see [Setting your email in Git](https://help.github.com/articles/setting-your-email-in-git/).

#### Available commands
To create a new persona the `add` command is available. It allows you to define an alias linked to persona details, which are basically the user name and email. Every added persona is stored in the global storage.
``` bash
git-user-bend add <alias> <name> <email>
git-user-bend add "oss" "Raphael Stolt" "raphaelstolt@users.noreply.github.com"
git-user-bend add "com" "Raphael Stolt" "raphael.stolt@company.com"
```

To create a persona from a local `.gub` dotfile, local Git repository user details, or from global Git user details the `import` command can be used. When a persona should be created from the Git user details its alias has to be provided.
``` bash
git-user-bend import [<alias>] [<directory>] [--from-dotfile]
```

To create a local `.gub` dotfile from an existing persona the `export` command is available.
``` bash
git-user-bend export <alias> [<directory>]
```

To remove a defined persona from the global storage the `retire` command can be used.
``` bash
git-user-bend retire "oss"
```

To view all defined personas the `personas` command is at your service. Via the `--edit|-e` option the global storage file called `.gub.personas` will be editable via the defined `$EDITOR`.
``` bash
git-user-bend personas [--edit|-e]
```

To bend the persona of a Git repository, the `use` command is there to change the Git user configuration to the aliased user details. When using the `--from-dotfile` option the persona defined in a `.gub` dotfile is used. When an aliased persona from the global storage should be used its alias has to be provided. When a pair should be used their aliases have to be provided as a comma-separated list.
``` bash
git-user-bend use [<alias>|<aliases>] [<directory>] [--from-dotfile]
```

To start a pair programming session, which will be identifiable in the Git commits, the `pair` command merges the user details of several personas into one pair. The email of the first persona alias in the comma-separated list will be used for the Git `user.email` configuration.
``` bash
git-user-bend pair "paul,sarah" [<directory>]
```

To check the persona, pair or respectively the Git user configuration of a repository the `whoami` command is a pleasant shortcut.
``` bash
git-user-bend whoami [<directory>]
```

#### Running tests
``` bash
composer test
```

#### License
This library and its CLI are licensed under the MIT license. Please see [LICENSE.md](LICENSE.md) for more details.

#### Changelog
Please see [CHANGELOG.md](CHANGELOG.md) for more details.

#### Code of Conduct
Please see [CONDUCT.md](CONDUCT.md) for more details.

#### Contributing
Please see [CONTRIBUTING.md](CONTRIBUTING.md) for more details.
