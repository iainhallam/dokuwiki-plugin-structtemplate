#!/usr/bin/env bash

if [[ ! $# == 2 ]] ; then
  echo "Use two arguments for the DokuWiki path and protected directory"
	echo
	echo "Usage:"
	echo "  $0 DW_PATH PROTECTED_DIR"
	echo
  echo "DW_PATH is any directory that can be resolved by the script, e.g."
  echo "  /var/www/public"
  echo "  ./"
	echo "PROTECTED_DIR is a path relative to the DokuWiki root, e.g."
	echo "  lib/plugins/usermanager"
	exit 1
fi

# Configuration ========================================================

# Script constants -----------------------------------------------------

SUCCESS=0
FAILURE=1

# Script variables -----------------------------------------------------

DW_PATH=$1
PROTECTED_DIR=$2

# Script ===============================================================

# Check DW_PATH exists
if [[ ! -d "${DW_PATH}" ]] ; then
  echo "ERROR: DW_PATH ${DW_PATH} is not a directory"
  exit ${FAILURE}
fi
# Check DW_PATH contains a DokuWiki installation
if [[ ! -e "${DW_PATH}/doku.php" ]] ; then
  echo "ERROR: DW_PATH ${DW_PATH} does not contain a DokuWiki installation (checked for doku.php)"
  exit ${FAILURE}
fi
# Check PROTECTED_DIR exists
if [[ ! -d "${DW_PATH}/${PROTECTED_DIR}" ]] ; then
  echo "ERROR: PROTECTED_DIR ${DW_PATH}/${PROTECTED_DIR} is not a directory"
  exit ${FAILURE}
fi

for file in $(find "${DW_PATH}" -type f -depth | grep -v "^${DW_PATH}/${PROTECTED_DIR}/") ; do
  rm ${file}
done

for dir in $(
  find "${DW_PATH}" -mindepth 1 -type d | \
  grep -v "^${DW_PATH}/${PROTECTED_DIR}$" | \
  grep -v "^${DW_PATH}/${PROTECTED_DIR}/" | \
  grep -v "^${DW_PATH}/lib/plugins$" | \
  grep -v "^${DW_PATH}/lib$" \
) ; do
  rmdir ${dir}
done

exit ${SUCCESS}
