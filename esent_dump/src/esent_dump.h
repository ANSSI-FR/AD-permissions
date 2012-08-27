/*
Copyright or © or Copr. Geraud de Drouas, 2012.

geraud.de-drouas@ssi.gouv.fr

This software is a computer program whose purpose is to retrieve Active
Directory objects permissions from an ESENT database file.

This software is governed by the CeCILL license under French law and
abiding by the rules of distribution of free software.  You can  use, 
modify and/ or redistribute the software under the terms of the CeCILL
license as circulated by CEA, CNRS and INRIA at the following URL
"http://www.cecill.info". 

As a counterpart to the access to the source code and  rights to copy,
modify and redistribute granted by the license, users are provided only
with a limited warranty  and the software's author,  the holder of the
economic rights,  and the successive licensors  have only  limited
liability. 

In this respect, the user's attention is drawn to the risks associated
with loading,  using,  modifying and/or developing or reproducing the
software by the user in light of its specific status of free software,
that may mean  that it is complicated to manipulate,  and  that  also
therefore means  that it is reserved for developers  and  experienced
professionals having in-depth computer knowledge. Users are therefore
encouraged to load and test the software's suitability as regards their
requirements in conditions enabling the security of their systems and/or 
data to be ensured and,  more generally, to use and operate it in the 
same conditions as regards security. 

The fact that you are presently reading this means that you have had
knowledge of the CeCILL license and that you accept its terms.
*/

//ATT to LDAP display names dictionary
unsigned char * translateATT(
	IN unsigned char * columnListName
	);

//Is current column interesting for us?
int ValidateColumn(
	IN unsigned char *main_arg,
	IN unsigned char *columnListName
	);

/* Dump ACE to file line per line in the following format:
*  ownerSID, groupSID, ACEType, ACEFlags, AccessMask, (Flags), (ObjectType guid), (InheritedObjectType guid), TrusteeSID
*/
void DumpACE(
			 IN long long sd_id,
			 IN unsigned char *buffer,
			 IN FILE *dump
				);

