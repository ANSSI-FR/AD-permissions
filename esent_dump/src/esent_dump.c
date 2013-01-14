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


// define JET_VERSION to be at least 0x0501 to get access to  JetCreateInstance. That means this
// program will run on Windows XP and up (Windows Server 2003 and up).
// Though XP will probably have trouble to open databases, especially from later OS versions


#undef JET_VERSION

#define JET_VERSION 0x0501

#define MAX_GUID_LENGTH 78

#include <stdio.h>

#include <string.h>

#include <esent.h>

#include <iads.h>

#include <Sddl.h>

#include <Rpc.h>

#pragma comment( lib, "esent" )

#pragma comment( lib, "Rpcrt4" )

#include "esent_dump.h"

//Global var for exchange SD column name, depends on schema
char exchangeMailboxSDCol[32]="ATTp";

#define NAME_SIZE 256
#define JET_BUFFER_SIZE 65536

void PrintUsage()
{
	printf("Usage: esent_dump [option] <ntdsfile>\n");
	printf("Options, mutually exclusive, do 1st 4 for ACE audit:\n");
	printf("\tatt: dump column naming columns\n");
	printf("\tsid: dump the Common-Name/OU-Name to SID, Object-Category, NT SD and Mailbox SD indexes\n");
	printf("\tace: dump the AD ACE from sd_table\n");
	printf("\tcat: dump object categories number to description\n");
	printf("\tad: dump the whole AD datatable\n");
	exit(-1);	
}



//ATT to LDAP display names dictionary
unsigned char * translateATT(
	IN unsigned char * columnListName
	)
{
	if(!strcmp(columnListName, exchangeMailboxSDCol))
		return "ms-Exch-Mailbox-Security-Descriptor";

	switch(atoi(columnListName + 4)) {
	case 3: 
		return "Common-Name";
	case 11:
		return "Organizational-Unit-Name";
	case 1376281:
		return "Domain-Component";
	case 131532:
		return "LDAP-Display-Name";
	case 590480:
		return "User-Principal-Name";
	case 131102: 
		return "Attribute-ID";
	case 591540:
		return "msDS-IntId";
	case 590689:
		return "Pek-List";
	case 590045:
		return "Username";
	case 589879:
		return "DBCS-Pwd";
	case 589914:
		return "Unicode-Pwd";
	case 589970:
		return "Object-Sid";
	case 590433:
		return "Sid-History";
	case 589832:
		return "User-Account-Control";
	case 589949:
		return "Supplemental-Credentials";
	case 590606:
		return "Object-Category";
	case 590607:
		return "Default-Object-Category";
	case 131353:
		return "NT-Security-Descriptor";
	case 589972:
		return "Schema-ID-GUID";
	case 590164:
		return "Rights-Guid";
	}
	return "";
}



//Is current column interesting for us?
int ValidateColumn(
	IN unsigned char *main_arg,
	IN unsigned char *columnListName
	)
{
	if(!strcmp("ad",main_arg)
		|| (!strcmp("ace",main_arg) && (!strcmp("sd_value",columnListName) || !strcmp("sd_id",columnListName)))
		|| (!strcmp("sid",main_arg) && (!strcmp("ATTp131353",columnListName) || !strcmp("ATTr589970",columnListName) || !strcmp("ATTm3",columnListName) || !strcmp("ATTm11",columnListName) || !strcmp("ATTk589972",columnListName) || !strcmp("ATTb590606",columnListName) || !strcmp("ATTm590164",columnListName) || !strcmp(exchangeMailboxSDCol,columnListName)))
		|| (!strcmp("att",main_arg) && (!strcmp("ATTm131532",columnListName) || !strcmp("ATTc131102",columnListName) || !strcmp("ATTj591540",columnListName)))
		|| (!strcmp("cat",main_arg) && (!strcmp("ATTm131532",columnListName) || !strcmp("ATTb590607",columnListName)))
		)
		return 1;
	else
		return 0;

}



/* Dump ACE to file line per line in the following format:
*  ownerSID, groupSID, ACEType, ACEFlags, AccessMask, (Flags), (ObjectType guid), (InheritedObjectType guid), TrusteeSID
*/
void DumpACE(
	IN long long sd_id,
	IN unsigned char *buffer,
	IN FILE *dump
	)
{

	BOOL daclPresent, daclDefaulted;
	PACL dacl;

	PSID owner, group;
	BOOL ownerDefaulted, groupDefaulted;
	LPVOID ace;

	LPWSTR stringOwner = NULL;
	LPWSTR stringGroup = NULL;
	LPWSTR stringTrustee = NULL;
	RPC_WSTR OTGuid = NULL;
	RPC_WSTR IOTGuid = NULL;

	unsigned int i;


	GetSecurityDescriptorOwner(buffer, &owner, &ownerDefaulted);
	ConvertSidToStringSid(owner, &stringOwner);

	GetSecurityDescriptorGroup(buffer, &group, &groupDefaulted);
	ConvertSidToStringSid(group, &stringGroup);

	GetSecurityDescriptorDacl(buffer, &daclPresent, &dacl, &daclDefaulted);


	for(i = 0 ; GetAce(dacl, i, &ace) ; i++)
	{
		//Remove inherited ACE
		if((((ACE_HEADER *)ace)->AceFlags & INHERITED_ACE) != INHERITED_ACE)
		{
			fwprintf(dump,L"\n");			
			
			//Standard allow&deny ACE
			if(((ACE_HEADER *)ace)->AceType < 0x5)
			{	
				ConvertSidToStringSid((PSID)&(((ACCESS_ALLOWED_ACE *)ace)->SidStart), &stringTrustee);
				fwprintf(dump, L"%lld\t%s\t%s\t%.2X\t%.2X\t%d\t\t\t\t%s",
					sd_id,
					stringOwner,
					stringGroup,
					((ACE_HEADER *)ace)->AceType, 
					((ACE_HEADER *)ace)->AceFlags, 
					((ACCESS_ALLOWED_ACE *)ace)->Mask,
					stringTrustee 
					);

			}
			//Object ACE
			else
			{
				switch(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->Flags)
				{
					//not any OT
				case 0x0:
					{
						ConvertSidToStringSid((PSID)((DWORD)&(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->SidStart) - 2 * sizeof(GUID)), 
							&stringTrustee
							);
						wcsncpy_s(OTGuid, MAX_GUID_LENGTH, L"",2);
						wcsncpy_s(IOTGuid, MAX_GUID_LENGTH, L"",2);
						break;
					}

					//Only OT
				case 0x1:
					{
						UuidToString(&(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->ObjectType), &OTGuid);
						ConvertSidToStringSid((PSID)((DWORD)&(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->SidStart) - sizeof(GUID)), 
							&stringTrustee
							);
						wcsncpy_s(IOTGuid, MAX_GUID_LENGTH, L"",2);
						break;
					}
					//Only IOT
				case 0x2:
					{
						UuidToString(&(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->InheritedObjectType), &IOTGuid);
						ConvertSidToStringSid((PSID)((DWORD)&(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->SidStart) - sizeof(GUID)), 
							&stringTrustee
							);
						wcsncpy_s(OTGuid, MAX_GUID_LENGTH, L"",2);
						break;

					}
					//both
				case 0x3:
					{
						UuidToString(&(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->ObjectType), &OTGuid);
						UuidToString(&(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->InheritedObjectType), &IOTGuid);
						ConvertSidToStringSid((PSID)&(((ACCESS_ALLOWED_OBJECT_ACE *)ace)->SidStart), 
							&stringTrustee
							);
						break;
					}
				}

				fwprintf(dump, L"%lld\t%s\t%s\t%.2X\t%.2X\t%d\t%d\t%s\t%s\t%s",
					sd_id,
					stringOwner,
					stringGroup,
					((ACE_HEADER *)ace)->AceType,
					((ACE_HEADER *)ace)->AceFlags,
					((ACCESS_ALLOWED_OBJECT_ACE *)ace)->Mask, 
					((ACCESS_ALLOWED_OBJECT_ACE *)ace)->Flags,
					OTGuid,
					IOTGuid,
					stringTrustee 
					);
			}

			LocalFree(stringTrustee);
			stringTrustee = NULL;

			RpcStringFree(&OTGuid);
				OTGuid = NULL;
				
			RpcStringFree(&IOTGuid);
				IOTGuid = NULL;

		}	

	}

	LocalFree(stringOwner);
	stringOwner = NULL;

	LocalFree(stringGroup);
	stringGroup = NULL;

}





int main(int argc, char * argv[]) {

	//Linked list to contain the datatable columns metadata
	typedef	struct _COLUMNLIST {
		int type;
		int id;
		char name[NAME_SIZE];
		struct _COLUMNLIST * next;
	}COLUMNLIST;

	COLUMNLIST *columnList;
	COLUMNLIST *listHead = (COLUMNLIST *)malloc(sizeof(COLUMNLIST));

	JET_ERR err;
	JET_INSTANCE instance = JET_instanceNil;
	JET_SESID sesid;
	JET_DBID dbid;
	JET_TABLEID tableid ;

	/*
	JET_COLUMNDEF *columndefid = malloc(sizeof(JET_COLUMNDEF));
	JET_COLUMNDEF *columndeftype = malloc(sizeof(JET_COLUMNDEF));
	JET_COLUMNDEF *columndeftypecol = malloc(sizeof(JET_COLUMNDEF));
	JET_COLUMNDEF *columndefname = malloc(sizeof(JET_COLUMNDEF));
	JET_COLUMNDEF *columndefobjid = malloc(sizeof(JET_COLUMNDEF));
	*/

	JET_COLUMNDEF _columndefid;
	JET_COLUMNDEF _columndeftype;
	JET_COLUMNDEF _columndeftypecol;
	JET_COLUMNDEF _columndefname;
	JET_COLUMNDEF _columndefobjid;

	JET_COLUMNDEF *columndefid = &_columndefid;
	JET_COLUMNDEF *columndeftype = &_columndeftype;
	JET_COLUMNDEF *columndeftypecol = &_columndeftypecol;
	JET_COLUMNDEF *columndefname = &_columndefname;
	JET_COLUMNDEF *columndefobjid = &_columndefobjid;

	unsigned long a,b,c,d,e;
	long bufferid[16];
	char buffertype[256];
	char buffertypecol[8];
	char buffername[NAME_SIZE];
	long bufferobjid[8];

	//Actually max buffer size should depend on the page size but it doesn't. Further investigation required.
	unsigned char jetBuffer[JET_BUFFER_SIZE];
	unsigned long jetSize;

	char *baseName = argv[2];
	char *targetTable;
	char *tableName;
	unsigned int datatableId = 0xffffffff;
	unsigned int i;

	FILE *dump;
	char dumpFileName[64];
	//SYSTEMTIME lt;

	RPC_WSTR Guid = NULL;

	LPWSTR stringSid = NULL;
	long long sd_id = 0;

	listHead->next = NULL;
	columnList = listHead;

	if( argc < 3)
		PrintUsage();

	if(!strcmp(argv[1],"ad") || !strcmp(argv[1],"sid") || !strcmp(argv[1],"att") || !strcmp(argv[1],"cat") || !strcmp(argv[1],"users"))
		targetTable = "datatable";
	else if(!strcmp(argv[1],"ace"))
		targetTable = "sd_table";
	else
		PrintUsage();

	if(!strcmp(argv[1],"sid"))
	{
		printf("To dump Exchange Mailbox security descriptors, \nenter the ATT value for your specific Exchange Schema:\n(msDS-IntId value for msExchMailboxSecurityDescriptor, \nfound in 'esent_dump att' results)\n");
		printf("Otherwise just input anything and press enter\n");
		scanf_s("%s",&exchangeMailboxSDCol[4], 28);
	}

	//Our result file, don't modify if you want to use auto import scripts from dbbrowser
	//GetLocalTime(&lt);
	//sprintf_s(dumpFileName, 64, "%s_ntds_%02d-%02d-%04d_%02dh%02d.csv",argv[1], lt.wDay, lt.wMonth, lt.wYear, lt.wHour, lt.wMinute);
	sprintf_s(dumpFileName, 64, "%s-ntds.dit-dump.csv", argv[1]);
	fopen_s(&dump, dumpFileName, "w");
	if (dump == 0)
	{
		printf("Could not open csv file for writing\n");
		return(-1);
	}

	if(!strcmp(argv[1],"ace"))
		fprintf(dump, "sd_id\tPrimaryOwner\tPrimaryGroup\tACEType\tACEFlags\tAccessMask\tFlags\tObjectType\tInheritedObjectType\tTrusteeSID\n");

	// Initialize ESENT. 
	// See http://msdn.microsoft.com/en-us/library/windows/desktop/gg269297(v=exchg.10).aspx for error codes

	err = JetSetSystemParameter(0, JET_sesidNil, JET_paramDatabasePageSize, 8192, NULL);

	err = JetCreateInstance(&instance, "blabla");

	err = JetInit(&instance);

	err = JetBeginSession(instance, &sesid, 0, 0);

	err = JetAttachDatabase(sesid, baseName, JET_bitDbReadOnly);	
	if (err != 0)
	{
		printf("JetAttachDatabase : %i\n", err);
		return(-1);
	}

	err = JetOpenDatabase(sesid, baseName, 0, &dbid, 0);
	if (err != 0)
	{
		printf("JetOpenDatabase : %i\n", err);
		return(-1);
	}


	//Let's enumerate the metadata about datatable (AD table)

	tableName = "MSysObjects";

	err = JetOpenTable(sesid, dbid, tableName, 0, 0, JET_bitTableReadOnly, &tableid);

	printf("[*]Opened table: %s\n", tableName);

	//Obtain structures with necessary information to retrieve column values

	err = JetGetColumnInfo(sesid, dbid, tableName, "Id", columndefid, sizeof(JET_COLUMNDEF), JET_ColInfo);

	err = JetGetColumnInfo(sesid, dbid, tableName, "Type", columndeftype, sizeof(JET_COLUMNDEF), JET_ColInfo);

	err = JetGetColumnInfo(sesid, dbid, tableName, "ColtypOrPgnoFDP", columndeftypecol, sizeof(JET_COLUMNDEF), JET_ColInfo);

	err = JetGetColumnInfo(sesid, dbid, tableName, "Name", columndefname, sizeof(JET_COLUMNDEF), JET_ColInfo);

	err = JetGetColumnInfo(sesid, dbid, tableName, "ObjIdTable", columndefobjid, sizeof(JET_COLUMNDEF), JET_ColInfo);




	//printf("Type de colonne :%d, de longueur : %d", columndef->coltyp, columndef->cbMax);


	//Position the cursor at the first record
	JetMove(sesid, tableid, JET_MoveFirst, 0);
	//Retrieve columns metadata	
	do
	{
		JetRetrieveColumn(sesid, tableid, columndefid->columnid, 0, 0, &a, 0, 0);
		JetRetrieveColumn(sesid, tableid, columndefid->columnid, bufferid, a, 0, 0, 0);

		JetRetrieveColumn(sesid, tableid, columndeftype->columnid, 0, 0, &b, 0, 0);
		JetRetrieveColumn(sesid, tableid, columndeftype->columnid, buffertype, b, 0, 0, 0);

		JetRetrieveColumn(sesid, tableid, columndeftypecol->columnid, 0, 0, &e, 0, 0);
		JetRetrieveColumn(sesid, tableid, columndeftypecol->columnid, buffertypecol, e, 0, 0, 0);

		JetRetrieveColumn(sesid, tableid, columndefname->columnid, 0, 0, &c, 0, 0);
		JetRetrieveColumn(sesid, tableid, columndefname->columnid, buffername, c, 0, 0, 0);
		buffername[c]='\0';
		if(datatableId == 0xffffffff && !strcmp(buffername, targetTable))
		{
			//We found the target table in the metadata, pickup its id and make another pass
			datatableId = bufferid[0];
			printf("[*]%s tableID found : %d\n", buffername, datatableId);
			JetMove(sesid, tableid, JET_MoveFirst, 0);
			continue;
		}

		JetRetrieveColumn(sesid, tableid, columndefobjid->columnid, 0, 0, &d, 0, 0);
		JetRetrieveColumn(sesid, tableid, columndefobjid->columnid, bufferobjid, d, 0, 0, 0);


		//We got the correct type and table id, let's dump the column name and add it to the column list
		if(buffertype[0] == 2 && bufferobjid[0] == datatableId) 
		{
			unsigned int j;
			columnList->next = (COLUMNLIST *)malloc(sizeof(COLUMNLIST));
			if(!columnList->next) {
				printf("Memory allocation failed during metadata dump\n");
				return(-1);
			}
			columnList = columnList->next;
			columnList->next = NULL;

			for(j=0;j<c;j++)
				columnList->name[j] = buffername[j];
			columnList->name[c] = '\0';
			columnList->type = buffertypecol[0];
			columnList->id = bufferid[0];
		}
	}while(JetMove(sesid, tableid, JET_MoveNext, 0) == JET_errSuccess);

	JetCloseTable(sesid, tableid);


	//Let's use our metadata to dump the whole AD schema
	tableName = targetTable;

	err = JetOpenTable(sesid, dbid, tableName, 0, 0, JET_bitTableReadOnly, &tableid);
	printf("[*]Opened table: %s\n", tableName);


	printf("Dumping %s column names...\n", tableName);
	columnList = listHead;
	while(columnList->next)
	{
		columnList = columnList->next;
		if(!strcmp("ad",argv[1]))
			fprintf(dump,"%d:%s\t",columnList->type,columnList->name);
		else
			if(ValidateColumn(argv[1], columnList->name))
				fprintf(dump, "%s\t", translateATT(columnList->name));
	};
	fprintf(dump,"\n");

	printf("Dumping content...\n");

	JetMove(sesid, tableid, JET_MoveFirst, 0);
	do
	{
		columnList = listHead;
		while(columnList->next)
		{
			columnList = columnList->next;

			if(ValidateColumn(argv[1], columnList->name))
			{
				//NOTE that this approach implies post processing multi valued columns if you re-use this code...
				err = JetRetrieveColumn(sesid, tableid, columnList->id, 0, 0, &jetSize, 0, 0);

#ifdef _DEBUG 
				//positive are warnings, -1047 is invalid buffer size which is expected here
				if (err < 0 && err != -1047) {
					printf("JetRetrieveColumn error : %i, jetSize : %d\n", err, jetSize);
					return(-2);
				}

				if (jetSize > JET_BUFFER_SIZE) {
					printf("Jet Buffer incorrect size preset: %d bytes are needed\n",jetSize);
					return(-2);
				}
#endif

			
				memset(jetBuffer,0,JET_BUFFER_SIZE);

				switch(columnList->type) {
					//signed int types
				case 4:
					JetRetrieveColumn(sesid, tableid, columnList->id, jetBuffer, jetSize, 0, 0, 0);
					//Specific useraccountcontrol code, currently dead code
					if(!strcmp("users",argv[1]) && !strcmp("ATTj589832",columnList->name))
					{
						if(jetBuffer[0] & ADS_UF_ACCOUNTDISABLE)
							fprintf(dump,"disabled ");
						if(jetBuffer[0] & ADS_UF_DONT_EXPIRE_PASSWD)
							fprintf(dump,"dontexpire ");
						if(jetBuffer[0] & ADS_UF_LOCKOUT)
							fprintf(dump,"lockedout ");
						if(jetBuffer[0] & ADS_UF_ENCRYPTED_TEXT_PASSWORD_ALLOWED)
							fprintf(dump,"reversiblepwd ");
					}
					else
						fprintf(dump,"%d",*(int *)jetBuffer);
					/*
					fprintf(dump,"%u_",*(unsigned int *)jetBuffer);
					for(unsigned int i=0;i<jetSize;i++)
					fprintf(dump,"%.2X",jetBuffer[i]);
					*/
					break;
					//signed long long type
				case 5:
					JetRetrieveColumn(sesid, tableid, columnList->id, jetBuffer, jetSize, 0, 0, 0);
					if(!strcmp("sd_id",columnList->name))
						sd_id = *(long long *)jetBuffer;
					else
						fprintf(dump,"%lld",*(long long *)jetBuffer);
					break;
					//Raw binary types
				case 9:
					JetRetrieveColumn(sesid, tableid, columnList->id, jetBuffer, jetSize, 0, 0, 0);
					for(i=0;i<jetSize;i++)
						fprintf(dump,"%.2X",jetBuffer[i]);
					break;
				case 11:
					/* We check matches on security descriptor, then SID
					*  to display accordingly, otherwise hex dump
					*/
					JetRetrieveColumn(sesid, tableid, columnList->id, jetBuffer, jetSize, 0, 0, 0);

					if(!strcmp("sd_value",columnList->name) && IsValidSecurityDescriptor(jetBuffer))
					{
						//Correct sd_id because sd_id column is before sd_value column in sd_table
						DumpACE(sd_id, jetBuffer, dump);
					}
					else if(!strcmp("ATTr589970",columnList->name) && IsValidSid(jetBuffer))
					{
						//AD SID storage swaps endianness in RID bytes (yeah !) 
						unsigned char temp;
						temp = jetBuffer[24];
						jetBuffer[24] = jetBuffer[27];
						jetBuffer[27] = temp;
						temp = jetBuffer[25];
						jetBuffer[25] = jetBuffer[26];
						jetBuffer[26] = temp;

						ConvertSidToStringSid((PSID)jetBuffer, &stringSid);
						fwprintf(dump, L"%s", stringSid);
						LocalFree(stringSid);
						stringSid = NULL;
					}
					//NT Security Descriptor index to lookup in sd_table
					else if(!strcmp("sid",argv[1]) && ( !strcmp("ATTp131353",columnList->name) || !strcmp(exchangeMailboxSDCol,columnList->name) ))
					{
						fprintf(dump,"%d",*(int *)jetBuffer);
					}
					//Schema-Id-Guid
					else if(!strcmp("sid",argv[1]) && !strcmp("ATTk589972",columnList->name) )
					{
						UuidToString((UUID *)jetBuffer, &Guid);
						fwprintf(dump,L"%s",Guid);
						RpcStringFree(&Guid);
						Guid = NULL;
					}
					else //hex dump
						for(i=0;i<jetSize;i++)
							fprintf(dump,"%.2X",jetBuffer[i]);

					/* dumping type 11 as int (?)
						else
						{
						fprintf(dump,"%d",*(int *)jetBuffer);
						printf("dump type 11 as int : %d\n", *(int *)jetBuffer);
						}
						*/
						
					break;
					//widechar text types
				case 10:
				case 12:
					JetRetrieveColumn(sesid, tableid, columnList->id, jetBuffer, jetSize, 0, 0, 0);
					for(i=0;i<jetSize/2;i++)
						if((wchar_t)jetBuffer[2*i] != '\t' || (wchar_t)jetBuffer[2*i] != '\n' || (wchar_t)jetBuffer[2*i] != '\r')
							fwprintf(dump,L"%c",(wchar_t)jetBuffer[2*i]);
					break;
				};

				if(strcmp("ace",argv[1]))
					fprintf(dump,"\t");
			}
		}
		if(strcmp("ace",argv[1]))
			fprintf(dump,"\n");
	}while(JetMove(sesid, tableid, JET_MoveNext, 0) == JET_errSuccess);

	// cleanup
	printf("cleaning...\n");

	JetCloseTable(sesid, tableid);
	JetEndSession(sesid, 0);
	JetTerm(instance);
	fclose(dump);
	return 0;
}




