<?php
session_start();
//start session, session will be maintained for entire call
require_once "response.php"; //response.php is the kookoo xml preparation class file
require_once "category.php";
$r = new Response();

$r->setFiller("yes");

$fileName = "./kookoo_trace.log"; // create logs to trace your application behaviour
if (file_exists($fileName)) {
    $fp = fopen($fileName, 'a+') or die("can't open file");
} else {
    $fp = fopen($fileName, 'x+'); // or die("can't open file");
}
fwrite($fp, "----------- kookoo params ------------- \n ");
foreach ($_REQUEST as $k => $v) {
    fwrite($fp, "param --  $k =  $v \n ");
}
fwrite($fp, "----------- session params maintained -------------  \n");
foreach ($_SESSION as $k => $v) {
    fwrite($fp, "session params $k =  $v  \n");
}

if ($_REQUEST['event'] == "NewCall") {

    fwrite($fp, "-----------NewCall from kookoo  -------------  \n");
    // Every new call first time you will get below params from kookoo
    //                                        event = NewCall
    //                                         cid= caller Number
    //                                         called_number = sid
    //                                         sid = session variable
    //
    //You maintain your own session params store require data
    $_SESSION['caller_number'] = $_REQUEST['cid'];
    $_SESSION['kookoo_number'] = $_REQUEST['called_number'];
    $_SESSION['circle']        = $_REQUEST['circle'];
    //called_number is register phone number on kookoo
    //
    $_SESSION['session_id'] = $_REQUEST['sid'];
    //sid is unique callid for each call
    // you maintain one session variable to check position of your call
    //here i had maintain next_goto as session variable
    $_SESSION['application'] = 'women-safety';
    $_SESSION['next_goto']   = 'Menu1';
    $r->addPlayText('welcome to all women safety application. ', 4);
}
if ($_REQUEST['event'] == "Disconnect" || $_REQUEST['event'] == "Hangup") {
    //when users hangs up at any time in call  event=Disconnect
    // when applicatoin sends hangup event event=Disconnect

    //if users hang up the call in dial event you will get data ans status params also
    //$_SESSION['dial_record_url']=$_REQUEST['data'];
    //$_SESSION['dial_status']=$_REQUEST['status'];
    exit;
}

//////////////////////////////////////////////////////////
/////////////////////////////////////////////////
/////////// Women Safety Application ///////
////////////////////////////////////////////////
//////////////////////////////////////////////////////////
if ($_SESSION['application'] == 'women-safety') {

    if ($_SESSION['next_goto'] == 'Menu1') {
        $collectInput = new CollectDtmf();
        $collectInput->addPlayText('press 1 for recording safety message, press 2 for helpline', 4);
        $collectInput->setMaxDigits('1'); //max inputs to be allowed
        $collectInput->setTimeOut('4000'); //maxtimeout if caller not give any inputs
        $r->addCollectDtmf($collectInput);
        $_SESSION['next_goto'] = 'Menu1_CheckInput';
    } else if ($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'Menu1_CheckInput') {
        //input will come data param
        //print parameter data value
        if ($_REQUEST['data'] == '') {
            //if value null, caller has not given any dtmf
            //no input handled
            $r->addPlayText('you have not entered any input');
            $_SESSION['next_goto'] = 'Menu1';
        } else if ($_REQUEST['data'] == '1') {
            $_SESSION['next_goto'] = 'Record_Status';
            $r->addPlayText('Please provide your location details and your current situation');
            $r->addPlayText('Record your message after beep ');
            //give unique file name for each recording
            $r->addRecord('message', 'wav', '120');
        } else if ($_REQUEST['data'] == '2') {
            $_SESSION['next_goto'] = 'Dial1_Status1';
            $r->addDial("181", 'true', 1000, 30, 'default');
        } else {
            $r->addPlayText('Thats an invalid input');
            $_SESSION['next_goto'] = 'Menu1';
        }
    } else if ($_REQUEST['event'] == 'Dial' && $_SESSION['next_goto'] == 'Dial1_Status1') {
//dial url will come data param  //if dial record false then data value will be -1 or null
        //dial status will come in status (answered/not_answered) param
        //print parameter data and status params value
        $_SESSION['dial_record_url']   = $_REQUEST['data'];
        $_SESSION['dial_status']       = $_REQUEST['status'];
        $_SESSION['dial_callduration'] = $_REQUEST['callduration'];
        if ($_REQUEST['status'] == 'not_answered') {
            //if you would like dial another number, if first call not answered,
            //
            $r->addPlayText('Number 181 not working going state specific numbers');
            $_SESSION['next_goto'] = 'DialMenu';
        } else {
            $r->addPlayText('Thank you for calling, ');
            $r->addHangup(); // do something more or send hang up to kookoo
            // call is answered
        }

    } else if ($_SESSION['next_goto'] == 'DialMenu') {

        $collectInput = new CollectDtmf();

        // Possible Locations
        $locations = array('BIHAR and JHARKHAND', 'MUMBAI', 'MAHARASHTRA', 'TAMILNADU', 'ANDHRA PRADESH', 'PUNJAB', 'NORTH EAST', 'UTTAR PRADESH (E)', 'UTTAR PRADESH (W) and UTTARAKHAND', 'KARNATAKA', 'MADHYA PRADESH and CHHATISGARH', 'KERALA', 'HARYANA', 'GUJARAT', 'ORISSA', 'WEST BENGAL', 'CHENNAI', 'KOLKATA', 'JAMMU and KASHMIR', 'ASSAM', 'DELHI', 'RAJASTHAN', 'HIMACHAL PRADESH');

        if ($_SESSION['circle'] == 'DELHI') {
            $collectInput->addPlayText('Press 1 for National Commission for Women', 4);
            $collectInput->addPlayText('Press 2 for Delhi Commission for Women', 4);
            $collectInput->addPlayText('Press 3 for Women Protection Cell', 4);
            $collectInput->addPlayText('Press 4 for Central Social Welfare Board', 4);
        } else if ($_SESSION['circle'] == 'ANDHRA PRADESH') {
            $collectInput->addPlayText('Press 1 for National Commission for Women', 4);
            $collectInput->addPlayText('Press 2 for Women Protection Cell', 4);
            $collectInput->addPlayText('Press 3 for Women Police Station', 4);
            $collectInput->addPlayText('Press 4 for Women network', 4);
        } else if ($_SESSION['circle'] == 'BIHAR and JHARKHAND') {
            $collectInput->addPlayText('Press 1 for Women Helpline Centre number 1', 4);
            $collectInput->addPlayText('Press 2 for Women Helpline Centre number 2', 4);
            $collectInput->addPlayText('Press 3 for Women Helpline Centre number 3', 4);
        } else if ($_SESSION['circle'] == 'PUNJAB') {
            $collectInput->addPlayText('Press 1 for Women commission', 4);
            $collectInput->addPlayText('Press 2 for Women Helpline', 4);
            $collectInput->addPlayText('Press 3 for Samvad', 4);
        } else if ($_SESSION['circle'] == 'RAJASTHAN') {
            $collectInput->addPlayText('Press 1 for Women Helpline', 4);
        } else if ($_SESSION['circle'] == 'HARYANA') {
            $collectInput->addPlayText('Press 1 for Women and Child Helpline', 4);
            $collectInput->addPlayText('Press 2 for Helpline for Women in Distress', 4);
        } else if ($_SESSION['circle'] == 'GUJARAT') {
            $collectInput->addPlayText('Press 1 for Ahmadabad Women ActionGroup', 4);
            $collectInput->addPlayText('Press 2 for Self Employed Women Association', 4);
        } else if ($_SESSION['circle'] == 'HIMACHAL PRADESH') {
            $collectInput->addPlayText('Press 1 for Women commission number 1', 4);
            $collectInput->addPlayText('Press 2 for Women commission number 2', 4);
            $collectInput->addPlayText('Press 3 for Women commission number 3', 4);
            $collectInput->addPlayText('Press 4 for Women commission number 4', 4);
        } else if ($_SESSION['circle'] == 'KARNATAKA') {
            $collectInput->addPlayText('Press 1 for Women commission number 1', 4);
            $collectInput->addPlayText('Press 2 for Women commission number 2', 4);
            $collectInput->addPlayText('Press 3 for Women commission number 3', 4);
            $collectInput->addPlayText('Press 4 for Women Helpline', 4);
        } else if ($_SESSION['circle'] == 'KERALA') {
            $collectInput->addPlayText('Press 1 for Women commission number 1', 4);
            $collectInput->addPlayText('Press 2 for Women commission number 2', 4);
            $collectInput->addPlayText('Press 3 for Women commission number 3', 4);
            $collectInput->addPlayText('Press 4 for Women commission number 4', 4);
            $collectInput->addPlayText('Press 5 for Women commission number 5', 4);
        } else if ($_SESSION['circle'] == 'MADHYA PRADESH and CHHATISGARH') {
            $collectInput->addPlayText('Press 1 for S P Office', 4);
            $collectInput->addPlayText('Press 2 for Mahila Thana', 4);
            $collectInput->addPlayText('Press 3 for Pardeshipura', 4);
            $collectInput->addPlayText('Press 4 for Sanyogitaganj', 4);
            $collectInput->addPlayText('Press 5 for Pandrinath', 4);
            $collectInput->addPlayText('Press 6 for Mari Mata Banganga', 4);
            $collectInput->addPlayText('Press 7 for Juni Indore', 4);
            $collectInput->addPlayText('Press 8 for MIG', 4);
            $collectInput->addPlayText('Press 9 for  Mallharganj', 4);
            $collectInput->addPlayText('Press 10 for Chandan Nagar', 4);
            $collectInput->addPlayText('Press 11 for Sanwar', 4);
            $collectInput->addPlayText('Press 12 for Mhow', 4);
            $collectInput->addPlayText('Press 13 for Depalpur', 4);
            $collectInput->addPlayText('Press 14 for Women Commission', 4);
        } else if (($_SESSION['circle'] == 'MUMBAI') || ($_SESSION['circle'] == 'MAHARASHTRA')) {
            $collectInput->addPlayText('Press 1 for MAJLIS', 4);
            $collectInput->addPlayText('Press 2 for Women Right Initiative', 4);
            $collectInput->addPlayText('Press 3 for Human Rights Law Network', 4);
            $collectInput->addPlayText('Press 4 for Women Helpline', 4);
            $collectInput->addPlayText('Press 5 for Shree Aadhar Kendra', 4);
        } else if (($_SESSION['circle'] == 'TAMILNADU') || ($_SESSION['circle'] == 'CHENNAI')) {
            $collectInput->addPlayText('Press 1 for Women Commission', 4);
            $collectInput->addPlayText('Press 2 for Snehdi', 4);
            $collectInput->addPlayText('Press 3 for The Banyan', 4);
            $collectInput->addPlayText('Press 4 for Women Police Station Adayar', 4);
            $collectInput->addPlayText('Press 5 for Women Police Station Guindy', 4);
        } else if (($_SESSION['circle'] == 'UTTAR PRADESH (E)') || ($_SESSION['circle'] == 'UTTAR PRADESH (W) and UTTARAKHAND')) {
            $collectInput->addPlayText('Press 1 for Sahyog', 4);
            $collectInput->addPlayText('Press 2 for Vanangana', 4);
            $collectInput->addPlayText('Press 3 for Aali', 4);
            $collectInput->addPlayText('Press 4 for Women Commission', 4);
        } else if ($_SESSION['circle'] == 'WEST BENGAL') {
            $collectInput->addPlayText('Press 1 for women commission number 1', 4);
            $collectInput->addPlayText('Press 2 for women commission number 2', 4);
            $collectInput->addPlayText('Press 3 for Swayam', 4);
            $collectInput->addPlayText('Press 4 for Women Helpline', 4);
        } else {
            $_SESSION['circle'] = 'OTHERS';
            $collectInput->addPlayText('Press 1 for women Helpline number 1', 4);
            $collectInput->addPlayText('Press 2 for women Helpline number 2', 4);
        }

        $collectInput->setMaxDigits('2'); //max inputs to be allowed
        $collectInput->setTimeOut('4000'); //maxtimeout if caller not give any inputs
        $collectInput->setTermChar('#');
        $r->addCollectDtmf($collectInput);
        $_SESSION['next_goto'] = 'Menu1_CheckInput1';

    } else if ($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'Menu1_CheckInput1') {
//input will come in data param
        //print print parameter data value
        if ($_REQUEST['data'] == '') {
            //if value null, caller has not given any dtmf
            //no input handled
            $r->addPlayText('you have not entered any input');
            $_SESSION['next_goto'] = 'Menu1';
        } else {

            $numbers = array(
                'DELHI'                             => array('01123237166', '01123379181', '01124673366', '01123317004'),
                'ANDHRA PRADESH'                    => array('01113237166', '04023320539', '04027853508', '04027014394'),
                'BIHAR and JHARKHAND'               => array('18003456247', '06122320047', '06122214318'),
                'PUNJAB'                            => array('0172783607', '09781101091', '01722546389'),
                'RAJASTHAN'                         => array('01412744596'),
                'HARYANA'                           => array('01242335100', '09911599100'),
                'GUJARAT'                           => array('01127470036', '01125506477'),
                'HIMACHAL PRADESH'                  => array('09816066421', '09418636326', '09816882491', '09418384215'),
                'KARNATAKA'                         => array('08022100435', '08022862368', '0802216485', '08022942149'),
                'KERALA'                            => array('04712322590', '04712320509', ' 04712337589', ' 04712339878', ' 04712339882'),
                'MADHYA PRADESH and CHHATISGARH'    => array('10862522111', '10862434999', '10862435999', '10862523999', '10862342999', '10862423999,10862362999', '10862570111', '10862454201', '108623789147', '7321220999', '7324228100', '7322221100', '10862661802'),
                'MUMBAI'                            => array('77326661252', '77343411603', '', '77323439754', '77326111103', '77324394104'),
                'MAHARASHTRA'                       => array('77326661252', '77343411603', '', '77323439754', '77326111103', '77324394104'),
                'TAMILNADU'                         => array('04428592750', '0442446293', '04426530504', '04424415732', ' 044-23452586', '04424700011'),
                'CHENNAI'                           => array('04428592750', '0442446293', '04426530504', '04424415732', ' 044-23452586', '04424700011'),
                'UTTAR PRADESH (E)'                 => array('05222387010', '05198236985', '05222782066', '09415293666'),
                'UTTAR PRADESH (W) and UTTARAKHAND' => array('05222387010', '05198236985', '05222782066', '09415293666'),
                'WEST BENGAL'                       => array('913323595609', ' 913323210154', '03324863367', '913323595609'),
                'OTHERS'                            => array('181', '1091'),
            );

            $_SESSION['dial'] = $numbers[$_SESSION['circle']][(int) $_REQUEST['data'] - 1];
            $r->addPlayText('please wait while we transfer your call to helpline number');
            $r->addDial($_SESSION['dial'], 'true', 1000, 30, 'ring');
            $_SESSION['next_goto'] = 'Dial1_Status';
        }
    } else if ($_REQUEST['event'] == 'Record' && $_SESSION['next_goto'] == 'Record_Status') {
//recorded file will be come as  url in data param
        //print parameter data value
        
        //$r->addPlayText('your recorded audio is ');
        $_SESSION['record_url'] = $_REQUEST['data'];
        //$r->addPlayAudio($_SESSION['record_url']);

        $collectInput = new CollectDtmf();
        $collectInput->addPlayText('please enter friend number followed by hash, if it is s t d number, enter 0 as pre fix ', 3);
        $collectInput->setMaxDigits('15'); //max inputs to be allowed
        $collectInput->setTimeOut('4000'); //maxtimeout if caller not give any inputs
        $collectInput->setTermChar('#');
        $r->addCollectDtmf($collectInput);
        $_SESSION['next_goto'] = 'Menu1_CheckInput2';

    } else if ($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'Menu1_CheckInput2') {
        $r->sendSms('Your friend ' . $_SESSION['called_number'] . ' is in need goto url: ' . $_SESSION['record_url'], $_REQUEST['data']);
        $r->addPlayText('Your recorded message is sent to your friend successfully');
        $r->addPlayText('Till then be safe may god be with you');
        $r->addHangup();
    } else if ($_REQUEST['event'] == 'Dial' && $_SESSION['next_goto'] == 'Dial1_Status') {
        //dial url will come data param  //if dial record false then data value will be -1 or null
        //dial status will come in status (answered/not_answered) param
        //print parameter data and status params value
        $_SESSION['dial_record_url']   = $_REQUEST['data'];
        $_SESSION['dial_status']       = $_REQUEST['status'];
        $_SESSION['dial_callduration'] = $_REQUEST['callduration'];
        if ($_REQUEST['status'] == 'not_answered') {
            //if you would like dial another number, if first call not answered,
            $_SESSION['next_goto'] = 'DialMenu';
        } else {
            $r->addPlayText('Thank you for calling, ');
            $r->addHangup(); // do something more or send hang up to kookoo
            // call is answered
        }

    } else {
        //print you session param 'next_goto' and other details
        $r->addPlayText('Sorry, session and events not maintained properly, Thank you for calling, have nice day');
        $r->addHangup(); // do something more or to send hang up to kookoo
    }

}
////////////////////////////////////////////////
//////// End Of Women Safety Application ///////
////////////////////////////////////////////////

//print final response xml send to kookoo, It would help you to understand request response between kookoo and your application
//           $r->getXML();
//
//$logs->writelog("final response xml addedd  ".$r->getXML().PHP_EOL." ::::\t\t\t");
fwrite($fp, "----------- final xml send to kookoo  -------------  " . $r->getXML() . "\n");
$r->send();
