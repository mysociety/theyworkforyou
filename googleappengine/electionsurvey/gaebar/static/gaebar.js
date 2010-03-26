/* 		
	Gaebar (Google App Engine Backup and Restore) Beta 1

	A Naklab™ production sponsored by the <head> web conference - http://headconference.com

	Copyright (c) 2009 Aral Balkan. http://aralbalkan.com

	Released under the GNU GPL v3 License. See license.txt for the full license or read it here:
	http://www.gnu.org/licenses/gpl-3.0-standalone.html
	
										* * *

	OK, folks, here's how we're going to do this: The only way to backup this baby
	is to issue a series of calls from the client since we don't have long-running
	processes on Google App Engine. Not going to get into that here, we all know
	that it sucks and Google is hopefully working on it.
	
	What it does mean that you'll have to leave the client connected throughout
	the backup process. And it may take a while. So grab a drink, kick back, and relax!
	
	Also keep your local server running too so that your backup can be automatically
	downloaded.
*/

//
// Google App Engine can randomly return 500 errors. In this case, retrying _should_ 
// get us over the hurdle without ruining the backup. We'll retry five times (you 
// can raise or lower this limit here.)
//
NUM_RETRIES_ON_FAILURE = 5;

//
// Nothing below this point should require customization. 
// 

backupCancelled = false;
restoreCancelled = false;

NO_RETRY = -1;

lastCall = null;
numTries = 0;

$.postJSON = function(url, data, callback) {
	$.post(url, data, callback, "json");
};

function showStatusMessage(message){
	old_debug_value = $("#debug").attr('value');
	$("#debug").attr('value', message + "\n" + old_debug_value);			
}

function clearStatusMessage(){
	$("#debug").attr('value', '');
}

$(document).ajaxError(function(){
    if (window.console && window.console.error) {
        console.error(arguments);
    }

	errorCode = arguments[1].status;
	errorMessage = arguments[1].responseText;
	
	showStatusMessage(errorMessage);

	if (lastCall == NO_RETRY) {
		// Don't retry this call.
		alert("Got a " + errorCode + " error on a call that I can't retry. Backup cancelled.\n\nError " + errorCode + ": " + errorMessage);
		cancelBackup();
	} else if (numTries < NUM_RETRIES_ON_FAILURE) {
		// Try the call again.
		console.info("Got a " + errorCode + " error on Ajax call, trying again... (try "+ String(numTries+1)+")");
		numTries++;
		$.postJSON(currentCall, {}, backup_progress_handler);	
	} else {
		// Reached max retries.
		alert("Sorry, tried 5 times and still getting an error " + errorCode +". I'm giving up and cancelling the backup.\n\nError " + errorCode + "::: " + errorMessage);
		
		cancelBackup();
	}

});


function backup_progress_handler(data, textStatus) {

	// Reset the number of tries (in case a call failed earlier)
	numTries = 0;
	
	if (backupCancelled) {
		return;
	}
	
	// str = ''
	// for (i in data){
	// 	str += i + " = " + data[i] + "\n";
	// }
	// alert(str);
	
	if (textStatus != "success"){
		// Error
		showStatusMessage(textStatus);
		return;
	}

	// We ignore datastore errors (e.g., reference errors)
	// to create a verbatim copy of the datastore, reference
	// errors and all.
	if (data.datastore_error) {
		showStatusMessage(data.datastore_error_message);				
	}

	
	if (data.empty_datastore) {
		$("#backupDialog").fadeOut("slow");
		showDialog("#noDataInBackupDialog");
	}
				
	if (data.complete) {

		//$("#debug").html("Done! Backup complete!");
		// The backup is complete, forward to the local download server 
		// to have the backup sucked in to the development server.
		
		$("#backupDialog").fadeOut("slow");
		
		showDialog("#localServerDialog");
		
		window.location.assign(data.download_url);
		
	} else {
		//$("#debug").html("Last backed-up key: " + data.key +". Backing up next...");

		backupPercent = Math.round(Number(data.num_models_done) * 100 / Number(data.num_models));
		$("#backupStatus").progressBar(backupPercent, { barImage: 'static/progressbg_red.gif', boxImage: 'static/progressbar.gif'} );

		$("#currentModel").html(data.current_model);
		$("#currentIndex").html(data.current_index);
		$("#numRows").html(data.num_rows);
		$("#numCodeShards").html(data.num_shards);
		$("#lastKey").html(data.last_key);
		
		modelsRemainingStr = '';
		for (i = 0; i < data.models_remaining.length; i++) {
			modelsRemainingStr += data.models_remaining[i] + ', ';
		}
		$("#modelsRemaining").html(modelsRemainingStr);				
		
		$("#lastUpdate").html(data.modified_at);			
		
		if (data.new_backup) {
			// Display the general information only the first time as it's not updated
			$("#createdAt").html(data.created_at);
			//$("#allModels").html(data.all_models)
			$("#backupKey").html(data.key);		
			
			allModelsStr = '';
			for (i = 0; i < data.all_models.length; i++) {
				allModelsStr += data.all_models[i] + ', ';
			}
			// Remove last comma.
			allModelsStr = allModelsStr.substr(0, allModelsStr.length-2)
			$("#allModels").attr('value', allModelsStr);				
				
		}
		
		// Continue the backup
		currentCall = "/gaebar/backup-rows/?backup_key=" + data.key + '&last_key=' + data.last_key;
		lastCall = currentCall;
		$.postJSON(currentCall, {}, backup_progress_handler);
	}
}

//////////////////////////////////////////////////////////////////////
//
// Restore progress handler.
//
//////////////////////////////////////////////////////////////////////

function restoreProgressHandler(data, textStatus){
	
	if (restoreCancelled) {
		return;
	}
	
	if (textStatus != "success"){
		// Error
		$("#debug").html(textStatus)
		return
	}
				
	if (data.complete) {

		// Hide the cancel dialog in case it's showing.
		removeConfirmCancelRestoreDialog();

		// The restore is complete.	
		showDialog("#restoreCompleteDialog");
		
						
	} else {

		restorePercent = Math.round(Number(data.row_index) * 100 / Number(data.num_rows));
		$("#restoreStatus").progressBar(restorePercent, { barImage: 'static/progressbg_red.gif', boxImage: 'static/progressbar.gif'} );
	
		// Update the status messages.				
		$("#lastRowRestored").html(data.row_index);
		$("#lastModelRestored").html(data.model);
				
		if (data.created_at){
			created_at_bits = data.created_at.split(" ");
			created_at_date = created_at_bits[0];
			created_at_time = created_at_bits[1].split(".")[0];
			$("#restoreBackupStartedAt").html(created_at_date + ' at ' + created_at_time);
		}
		
		$("#restoreNumberOfRows").html(data.num_rows);
		$("#restoreNumberOfShards").html(data.num_shards);
		
		$("#restoreCurrentShard").html(data.shard_number+1);
		
		modelsStr = ''
		for (i = 0; i < data.models.length; i++) {
			modelsStr += data.models[i] + ', ';
		}
		// Remove last comma.
		modelsStr = modelsStr.substr(0, modelsStr.length-2)
		$("#restoreAllModels").attr('value', modelsStr);
		
		// Recurse: call the backup rows method.
		url = "/gaebar/restore-row/?secret=" + data.secret + "&folder_name=" + data.folder_name + "&row_index=" + data.next_row_index;
		
		lastCall = NO_RETRY
		$.postJSON(url, restoreProgressHandler);
	}
	
}


function startBackup(eventObj){
	
	clearStatusMessage();
	
	backupCancelled = false;

	showDialog("#backupDialog");
	
	lastCall = NO_RETRY	
	$.postJSON("/gaebar/backup-start/", backup_progress_handler);
	return false;
}


function startRestore(eventObj){
	
	restoreCancelled = false;

	showDialog("#restoreDialog");
				
	folderName = $("#restoreSelect").attr("options")[$("#restoreSelect").attr("selectedIndex")].value;
	
	lastCall = NO_RETRY
	$.postJSON("/gaebar/get-restore-info/?folder_name=" + folderName, restoreProgressHandler);
	return false;
}



/* 
	Modal backup dialog functions.
	
	With thanks to Adrian "yEnS" Mato Gondelle (www.yensdesign.com/yensamg@gmail.com)
	for leading me in the right direction. Some of the code here is taken from his tutorial.
*/

// Show a modal dialog
function showDialog(dialog){
	// Center the dialog
	var windowWidth = $(window).width();
	var windowHeight = $(window).height();
	var popupHeight = $(dialog).height();
	var popupWidth = $(dialog).width();

	$(dialog).css({
		"position": "absolute",
		"top": windowHeight/2-popupHeight/2,
		"left": windowWidth/2-popupWidth/2
	});

	// Force the modal shade to take up the full vertical 
	// height of the browser window.
	$("#modalShade").css({
		"height": windowHeight
	});	

	// Set the modal shade's z-index to one lower than the 
	// actual dialog to make sure that it appears right behind it.
	$("#modalShade").css({"z-index": $(dialog).css("z-index")-1});

	// Fade in the modal shade and the dialog.
	$("#modalShade").fadeIn("slow");
	$(dialog).fadeIn("slow");
}


function confirmCancel(){
	showDialog("#confirmCancelDialog");
}

function confirmCancelRestore(){
	showDialog("#confirmCancelRestoreDialog");
}

function cancelBackup(){
	// Remove the popups
	removeConfirmCancelDialog();	
	$("#backupDialog").fadeOut("slow");
	$("#modalShade").fadeOut("slow");

	backupCancelled = true;
}

function cancelRestore(){
	// Remove the popups
	removeConfirmCancelRestoreDialog();	
	$("#restoreDialog").fadeOut("slow");
	$("#modalShade").fadeOut("slow");

	restoreCancelled = true;
}


function removeConfirmCancelDialog(){
	$("#modalShade").css({"z-index": 1});
	$("#confirmCancelDialog").fadeOut("slow");	
}

function removeConfirmCancelRestoreDialog(){
	$("#modalShade").css({"z-index": 1});
	$("#confirmCancelRestoreDialog").fadeOut("slow");	
}


function restoreCompleteDialogOKButtonHandler(eventObj){
	// Remove the restore complete and restore dialogs.
	$("#restoreCompleteDialog").fadeOut("slow");
	$("#restoreDialog").fadeOut("slow");
	$("#modalShade").fadeOut("slow");
}

function backupCompleteDialogOKButtonHandler(eventObj){
	// Remove the restore complete and restore dialogs.
	$("#backupCompleteDialog").fadeOut("slow");
	$("#modalShade").fadeOut("slow");
}


function noDataInBackupDialogOKButtonHandler(eventObj){
	// Remove the restore complete and restore dialogs.
	$("#noDataInBackupDialog").fadeOut("slow");
	$("#modalShade").fadeOut("slow");
}

function gaebar_init(){	
							
	$("#startBackupButton").click(startBackup);
	
	$("#startRestoreButton").click(startRestore);
	
	$("#restoreCompleteDialogOKButton").click(restoreCompleteDialogOKButtonHandler)
	$("#backupCompleteDialogOKButton").click(backupCompleteDialogOKButtonHandler)
	$("#noDataInBackupDialogOKButton").click(noDataInBackupDialogOKButtonHandler)
	
	//
	// Modal dialog event handlers
	//

	// Cancel backup button click handler
	$("#cancelBackupButton").click(function(){
		confirmCancel();
	});

	$("#cancelRestoreButton").click(function(){
		confirmCancelRestore();
	});

	// Confirmation dialog events
	$("#yes").click(function(){
		cancelBackup();
	});

	$("#no").click(function(){
		removeConfirmCancelDialog();
	});

	// Bah, refactoring is for wimps. OK, OK, I'll get round to it some day :)
	$("#yes2").click(function(){
		cancelRestore();
	});

	$("#no2").click(function(){
		removeConfirmCancelRestoreDialog();
	});
	
};
