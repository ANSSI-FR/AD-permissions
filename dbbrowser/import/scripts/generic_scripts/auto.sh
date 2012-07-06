#!/bin/bash
# Copyright or © or Copr. Pierre Capillon, 2012.
# 
# pierre.capillon@ssi.gouv.fr
# 
# This software is a computer program whose purpose is to retrieve Active
# Directory objects permissions from an ESENT database file.
# 
# This software is governed by the CeCILL license under French law and
# abiding by the rules of distribution of free software.  You can  use, 
# modify and/ or redistribute the software under the terms of the CeCILL
# license as circulated by CEA, CNRS and INRIA at the following URL
# "http://www.cecill.info". 
# 
# As a counterpart to the access to the source code and  rights to copy,
# modify and redistribute granted by the license, users are provided only
# with a limited warranty  and the software's author,  the holder of the
# economic rights,  and the successive licensors  have only  limited
# liability. 
# 
# In this respect, the user's attention is drawn to the risks associated
# with loading,  using,  modifying and/or developing or reproducing the
# software by the user in light of its specific status of free software,
# that may mean  that it is complicated to manipulate,  and  that  also
# therefore means  that it is reserved for developers  and  experienced
# professionals having in-depth computer knowledge. Users are therefore
# encouraged to load and test the software's suitability as regards their
# requirements in conditions enabling the security of their systems and/or 
# data to be ensured and,  more generally, to use and operate it in the 
# same conditions as regards security. 
# 
# The fact that you are presently reading this means that you have had
# knowledge of the CeCILL license and that you accept its terms.
# 

# $1 : fichier de donnee
# $2 : description
# $3 : path vers dbbrowser.php
# $4 : nom de la table à créer

if [ $# -ne 4 ]; then
	echo "Usage: $0 <fichier de données> <description> <dbbrowser path>"
	echo "<fichier de données>	Le fichier à importer qui contient <listAce> les donnees </listAce>"
	echo "<description>		Description servant de base de generation de nom de table (md5 de la descr)"
	echo "<dbbrowser path>		Le chemin absolu vers le fichier dbbrowser.php"
	echo "<table name>		Le nom de la table à créer"
	
	exit 1
fi

OWN_PATH="`dirname \"$0\"`"
OWN_PATH="`( cd \"$OWN_PATH\" && pwd )`"
if [ -z "$OWN_PATH" ] ; then
  exit 1
fi

cleanup="$OWN_PATH/cleanup.sh"
import="$OWN_PATH/import.sh"

script_path=$3
#hash=`echo -e "$2\c" | md5sum | cut -d' ' -f1`;
new_db_name=$4;

mkdir $new_db_name;
cp $1 $new_db_name/data.txt

$cleanup ./$new_db_name $new_db_name/data.txt $new_db_name
$import ./$new_db_name $new_db_name $script_path

mv $new_db_name "$script_path/data/"

echo "$new_db_name|$2" >> "$script_path/db_tables.txt"

