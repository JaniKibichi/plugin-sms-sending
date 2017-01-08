<?php
defined('_SECURE_') or die('Forbidden');

// hook_sendsms
// called by main sms sender
// return true for success delivery
// $smsc		: smsc
// $sms_sender	: sender mobile number
// $sms_footer	: sender sms footer or sms sender ID
// $sms_to		: destination sms number
// $sms_msg		: sms message tobe delivered
// $uid			: sender User ID
// $gpid		: group phonebook id (optional)
// $smslog_id	: sms ID
// $sms_type	: send flash message when the value is "flash"
// $unicode		: send unicode character (16 bit)

function africastalking_hook_sendsms($smsc, $sms_sender,$sms_footer,$sms_to,$sms_msg,$uid='',$gpid=0,$smslog_id=0,$sms_type='text',$unicode=0) {
	// global $tmpl_param;   // global all variables needed, eg: varibles from config.php
	global $plugin_config;
	$ok = false;	

	_log("enter smsc:" . $smsc . " smslog_id:" . $smslog_id . " uid:" . $uid . " to:" . $sms_to, 3, "africastalking_hook_sendsms");

	// override plugin gateway configuration by smsc configuration
	$plugin_config = gateway_apply_smsc_config($smsc, $plugin_config);	
	
		$sms_sender = stripslashes($sms_sender);
		if ($plugin_config['africastalking']['module_sender']) {
			$sms_sender = $plugin_config['africastalking']['module_sender'];
		}
		
		$sms_footer = stripslashes($sms_footer);
		$sms_msg = stripslashes($sms_msg);
		
		if ($sms_footer) {
			$sms_msg = $sms_msg . $sms_footer;
		}
	
	if ($sms_sender && $sms_to && $sms_msg) {

	    $params = array(
			    'username' => $plugin_config['africastalking']['api_key'],
			    'to'       => $sms_to,
			    'message'  => $sms_msg,
		);

	    if ( $sms_sender  !== null ) {
	      $params['from']        = $sms_sender ;
	      $params['bulkSMSMode'] = 1;
	    }

	    $requestUrl  = $plugin_config['africastalking']['url'];
	    $requestBody = http_build_query($params, '', '&');

	    if (function_exists('curl_init')) {
		    //$this->executePOST();
			    $ch = curl_init();
				curl_setopt($ch, CURLOPT_POSTFIELDS, $this->$requestBody);
				curl_setopt($ch, CURLOPT_POST, 1);
			//$this->doExecute($ch);
			   try {		   	
					//$this->setCurlOpts($ch);
					curl_setopt($ch, CURLOPT_TIMEOUT, 60);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_URL, $requestUrl);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Accept: application/json','apikey: ' . $plugin_config['africastalking']['api_secret']));
					$returns = curl_exec($ch);
					curl_close($ch);

				_log("sendsms url:[" . $requestUrl . "] callback:[" . $plugin_config['africastalking']['callback_url'], "] smsc:[" . $smsc . "]", 3, "africastalking_hook_sendsms");

				$resp = json_decode($returns);
				if ($resp->status) {
					$c_status = $resp->status;
					$c_message_id = $resp->sid;	
					
					//log and send to db
					_log("sent smslog_id:" . $smslog_id . " message_id:" . $c_message_id . " status:" . $c_status . " smsc:[" . $smsc . "]", 2, "africastalking_hook_sendsms");				

					$db_query = "
						INSERT INTO " . _DB_PREF_ . "_gatewayAfricastalking (local_smslog_id,remote_smslog_id,status,error_text)
						VALUES ('$smslog_id','$c_message_id','$c_status','NULL')";
					$id = @dba_insert_id($db_query);

					if ($id && ($c_status == 'sent')) {
						$ok = true;
						$p_status = 0;
					} else {
						$p_status = 2;
					}
					dlr($smslog_id, $uid, $p_status);					

				}else{
					// even when the response is not what we expected we still print it out for debug purposes
					$resp = str_replace("\n", " ", $resp);
					$resp = str_replace("\r", " ", $resp);
					_log("failed smslog_id:" . $smslog_id . " resp:" . $resp . " smsc:[" . $smsc . "]", 2, "africastalking_hook_sendsms");
				}

			   }catch(Exception $e) {
			    curl_close($ch);
			    throw $e;
			   }

		} else {
			_log("fail to sendsms due to missing PHP curl functions", 3, "africastalking_hook_sendsms");
		}

	}



	// return true or false
	if (!$ok) {
		$p_status = 2;
		dlr($smslog_id, $uid, $p_status);
	}
	
	_log("sendsms end", 3, "africastalking_hook_sendsms");

	// return $ok;	
	return $ok;
}

// hook_getsmsinbox
// called by incoming sms processor
// no returns needed
function africastalking_hook_call($requests) {
	// please note that we must globalize these 2 variables
	global $core_config, $plugin_config;
	$called_from_hook_call = true;
	$access = $requests['access'];
	if ($access == 'callback') {
		$fn = $core_config['apps_path']['plug'] . '/gateway/africastalking/callback.php';
		_log("start load:" . $fn, 2, "africastalking call");
		include $fn;
		_log("end load callback", 2, "africastalking call");
	}
}