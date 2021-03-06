#!/bin/bash
# ARG_OPTIONAL_SINGLE([path],[p],[Path to DokuWiki instance; overrides DW_PATH])
# ARG_POSITIONAL_INF([extension],[Extensions to install; start templates with template:])
# ARG_USE_ENV([DW_PATH],[./],[Path to DokuWiki instance])
# ARG_USE_ENV([DW_EXTENSIONS],[],[Extensions to install, space-separated])
# ARG_HELP([Install a plugin into a DokuWiki instance],[The DokuWiki instance must exist in the current directory, or be given via the\nenvironment variable DW_PATH, or as a command line option, which overrides the\nenvironment.\n \nExtensions can be given as command line arguments, or via the environment\nnvariable DW_EXTENSIONS, or both.])
# ARGBASH_SET_INDENT([  ])
# ARGBASH_GO()
# needed because of Argbash --> m4_ignore([
### START OF CODE GENERATED BY Argbash v2.9.0 one line above ###
# Argbash is a bash code generator used to get arguments parsing right.
# Argbash is FREE SOFTWARE, see https://argbash.io for more info
# Generated online by https://argbash.io/generate

# Setting environmental variables
# Setting environmental variables


die()
{
  local _ret="${2:-1}"
  test "${_PRINT_HELP:-no}" = yes && print_help >&2
  echo "$1" >&2
  exit "${_ret}"
}


begins_with_short_option()
{
  local first_option all_short_options='ph'
  first_option="${1:0:1}"
  test "$all_short_options" = "${all_short_options/$first_option/}" && return 1 || return 0
}

# THE DEFAULTS INITIALIZATION - POSITIONALS
_positionals=()
_arg_extension=()
# THE DEFAULTS INITIALIZATION - OPTIONALS
_arg_path=


print_help()
{
  printf '%s\n' "Install a plugin into a DokuWiki instance"
  printf 'Usage: %s [-p|--path <arg>] [-h|--help] [<extension-1>] ... [<extension-n>] ...\n' "$0"
  printf '\t%s\n' "<extension>: Extensions to install; start templates with template:"
  printf '\t%s\n' "-p, --path: Path to DokuWiki instance; overrides DW_PATH (no default)"
  printf '\t%s\n' "-h, --help: Prints help"
  printf '\nEnvironment variables that are supported:\n'
  printf '\t%s\n' "DW_PATH: Path to DokuWiki instance. (default: './')"
  printf '\t%s\n' "DW_EXTENSIONS: Extensions to install, space-separated."

  printf '\n%s\n' "The DokuWiki instance must exist in the current directory, or be given via the
environment variable DW_PATH, or as a command line option, which overrides the
environment.

Extensions can be given as command line arguments, or via the environment
nvariable DW_EXTENSIONS, or both."
}


parse_commandline()
{
  _positionals_count=0
  while test $# -gt 0
  do
    _key="$1"
    case "$_key" in
      -p|--path)
        test $# -lt 2 && die "Missing value for the optional argument '$_key'." 1
        _arg_path="$2"
        shift
        ;;
      --path=*)
        _arg_path="${_key##--path=}"
        ;;
      -p*)
        _arg_path="${_key##-p}"
        ;;
      -h|--help)
        print_help
        exit 0
        ;;
      -h*)
        print_help
        exit 0
        ;;
      *)
        _last_positional="$1"
        _positionals+=("$_last_positional")
        _positionals_count=$((_positionals_count + 1))
        ;;
    esac
    shift
  done
}


assign_positional_args()
{
  local _positional_name _shift_for=$1
  _positional_names=""
  _our_args=$((${#_positionals[@]} - 0))
  for ((ii = 0; ii < _our_args; ii++))
  do
    _positional_names="$_positional_names _arg_extension[$((ii + 0))]"
  done

  shift "$_shift_for"
  for _positional_name in ${_positional_names}
  do
    test $# -gt 0 || break
    eval "$_positional_name=\${1}" || die "Error during argument parsing, possibly an Argbash bug." 1
    shift
  done
}

parse_commandline "$@"
assign_positional_args 1 "${_positionals[@]}"

# OTHER STUFF GENERATED BY Argbash
test -n "$DW_PATH" || DW_PATH="./"


### END OF CODE GENERATED BY Argbash (sortof) ### ])
# [ <-- needed because of Argbash

# Configuration
# ======================================================================

declare -A paths

# Set paths
paths[old_wd]=$(pwd)
if [[ -n $_arg_path ]] ; then
  paths[dw]=$(realpath --canonicalize-missing "$_arg_path")
else
  paths[dw]=$(realpath --canonicalize-missing "$DW_PATH")
fi
paths[cli]="${paths[dw]}/bin/plugin.php"

cd "${paths[dw]}" || die "CRITICAL: Failed to change directory to ${paths[dw]}" 1
[[ -e ${paths[cli]} ]] || die "CRITICAL: DokuWiki plugin CLI not found at ${paths[dw]}" 1

paths[tmp]=$(mktemp --directory)
[[ -d ${paths[tmp]} ]] || die "CRITICAL: Temporary directory ${paths[tmp]} does not exist" 1

# Set extensions to install
read -ra _env_extension <<< "$DW_EXTENSIONS"
extensions=( "${_env_extension[@]}" "${_arg_extension[@]}" )

# Functions
# ======================================================================

function clean_up {
  if [[ -d ${paths[tmp]} ]] ; then rm -rf "${paths[tmp]}" ; fi
  cd "${paths[old_wd]}" || exit
}

# Script
# ======================================================================

trap clean_up EXIT

for extension in "${extensions[@]}" ; do
  echo >&2 "DEBUG: Installing extension ${extension}"

  php "${paths[cli]}" extension install "$extension"
done

# ] <-- needed because of Argbash
