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

echo "** cleanup.sh **" >> /tmp/cleanup.log
pwd >> /tmp/cleanup.log
echo "arg1: $1" >> /tmp/cleanup.log
echo "arg2: $2" >> /tmp/cleanup.log
echo "arg3: $3" >> /tmp/cleanup.log

#gawk -F'<listAce>' -v RS='</listAce>' 'RT{print $NF}' $2 > "$1/work.txt"
#iconv -f WINDOWS-1252 -t UTF-8 -o "$1/work2.txt" "$1/work.txt"
dos2unix -f "$2"
iconv -f WINDOWS-1252 -t UTF-8 -o "$1/work2.txt" "$2"
mv $1/work2.txt $1/work.txt
sed -e "s/\\\/\\\\\\\/g" -e "s///g" -e "s///g" -i "$1/work.txt"
sed '/^$/d' -i "$1/work.txt"

head -n 1 "$1/work.txt" > "$1/columns.txt"
sed 1d -i "$1/work.txt"

echo -e "DROP TABLE IF EXISTS \`$3\`;\n" >> $1/create2.sql
echo -e "CREATE TABLE IF NOT EXISTS \`$3\` (\n" >> $1/create2.sql

for c in `cat $1/columns.txt`; do
	special="false"
	for s in `cat columns_int.txt`; do
		if [ "$s" = "$c" ]; then
			echo -e "\`$c\` int DEFAULT NULL,\n" >> $1/create2.sql
			special="true"
			break
		fi
	done
	if [ "$special" = "false" ]; then
		echo -e "\`$c\` varchar(255) DEFAULT NULL,\n" >> $1/create2.sql
	fi
done
echo ") ENGINE=MyISAM DEFAULT CHARSET=utf8;" >> $1/create2.sql

sed ':a;N;$!ba;s/\n//g' -i "$1/create2.sql"
sed -e "s/,)/)/" -i "$1/create2.sql"

