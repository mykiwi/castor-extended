{ pkgs, lib, config, inputs, ... }:

{
  # https://devenv.sh/languages/
  languages.php = {
    enable = true;
    version = "8.5";
  };

  # https://devenv.sh/packages/
  packages = [
    pkgs.git
    pkgs.php85Packages.castor
  ];

  enterShell = ''
    php --version
    composer --version
    castor --version
  '';

  # https://devenv.sh/tests/
  enterTest = ''
    castor ci
  '';

  # See full reference at https://devenv.sh/reference/options/
}
