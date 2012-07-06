/*
Copyright or © or Copr. Pierre Capillon, 2012.

pierre.capillon@ssi.gouv.fr

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

	function displayTagForm(field,value) {
		var e, i;
		e = document.getElementById('tag_div');
		i = document.getElementById('add_tag_value');
		j = document.getElementById('add_tag_field');
		
		e.style.display = "none";
		i.value = value;
		j.value = field;
		e.style.display = "inline";
		return;
	}
	function displayGuidForm(guid) {
		var e, i;
		e = document.getElementById('guid_div');
		i = document.getElementById('guid_value');
		
		e.style.display = "none";
		i.value = guid;
		e.style.display = "inline";
		return;
	}
	function displayGuidIotForm(guid) {
		var e, i;
		e = document.getElementById('guid_iot_div');
		i = document.getElementById('guid_iot_value');
		
		e.style.display = "none";
		i.value = guid;
		e.style.display = "inline";
		return;
	}

	function updateAccessMask() {
		var e,i,r=0;
		for(i=1; i<=19; i++) {
			e = document.getElementById('mask_value_'+i);
			if(e.checked)
				r += parseInt(e.value);
		}
		e = document.getElementById('mask_final_value');
		e.value = '0x' + r.toString(16).toUpperCase();
	}
	
	function updateAccessMaskExch() {
		var e,i,r=0;
		for(i=1; i<=7; i++) {
			e = document.getElementById('mask_exch_value_'+i);
			if(e.checked)
				r += parseInt(e.value);
		}
		e = document.getElementById('mask_exch_final_value');
		e.value = '0x' + r.toString(16).toUpperCase();
	}
	
	function decodeAccessMask(mask) {
		
		var flags = new Array();
		var res = "";
		var i;
		
		flags[0x1]          = "ADS_RIGHT_DS_CREATE_CHILD";
		flags[0x2]          = "ADS_RIGHT_DS_DELETE_CHILD";
		flags[0x4]          = "ADS_RIGHT_ACTRL_DS_LIST";
		flags[0x8]          = "ADS_RIGHT_DS_SELF";
		flags[0x10]         = "ADS_RIGHT_DS_READ_PROP";
		flags[0x20]         = "ADS_RIGHT_DS_WRITE_PROP";
		flags[0x40]         = "ADS_RIGHT_DS_DELETE_TREE";
		flags[0x80]         = "ADS_RIGHT_DS_LIST_OBJECT";
		flags[0x100]        = "ADS_RIGHT_DS_CONTROL_ACCESS";
		flags[0x10000]      = "ADS_RIGHT_DELETE";
		flags[0x20000]      = "ADS_RIGHT_READ_CONTROL";
		flags[0x40000]      = "ADS_RIGHT_WRITE_DAC";
		flags[0x80000]      = "ADS_RIGHT_WRITE_OWNER";
		flags[0x100000]     = "ADS_RIGHT_SYNCHRONIZE";
		flags[0x1000000]    = "ADS_RIGHT_ACCESS_SYSTEM_SECURITY";
		flags[0x10000000]   = "ADS_RIGHT_GENERIC_ALL";
		flags[0x20000000]   = "ADS_RIGHT_GENERIC_EXECUTE";
		flags[0x40000000]   = "ADS_RIGHT_GENERIC_WRITE";
		flags[0x80000000]   = "ADS_RIGHT_GENERIC_READ";
		
		for(i=0;i<32;i++) {
			if((1<<i & mask) > 0) {
				res = res + "<li>" + '(0x' + (1<<i).toString(16).toUpperCase() + ") " + flags[1<<i] + "</li>\n";
			}
		}
		
		document.getElementById('decodedAccessMask_ul').innerHTML = res;
	}
	
	function decodeAccessMaskExch(mask) {
		
		var flags = new Array();
		var res = "";
		var i;
		
		flags[0x1]          = "RIGHT_DS_MAILBOX_OWNER";
		flags[0x2]          = "RIGHT_DS_SEND_AS";
		flags[0x4]          = "RIGHT_DS_PRIMARY_OWNER";
		flags[0x10000]      = "RIGHT_DS_DELETE";
		flags[0x20000]      = "RIGHT_DS_READ";
		flags[0x40000]      = "RIGHT_DS_CHANGE";
		flags[0x80000]      = "RIGHT_DS_TAKE_OWNERSHIP";

		for(i=0;i<32;i++) {
			if((1<<i & mask) > 0) {
				res = res + "<li>" + '(0x' + (1<<i).toString(16).toUpperCase() + ") " + flags[1<<i] + "</li>\n";
			}
		}
		
		document.getElementById('decodedAccessMask_ul').innerHTML = res;
	}
	
