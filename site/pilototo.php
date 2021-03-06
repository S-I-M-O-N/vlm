<?php

    /* Page pilototo re-entrante : gestion de la table auto_pilot pour l'utilisateur connecté */

    session_start();
    include_once("config.php");
    include_once("functions.php");


    /* ticket 542 : recuperer la conduite du pixel afin d'alimenter les PIM*/
    $targetlat =    isset($_SESSION['ptttargetlat']) ? $_SESSION['ptttargetlat'] : 0;
    $targetlong =   isset($_SESSION['ptttargetlong']) ? $_SESSION['ptttargetlong'] : 0;
    $boatheading =  isset($_SESSION['pttboatheading']) ? $_SESSION['pttboatheading'] : 0;
    $pilotmode =    isset($_SESSION['pttpilotmode']) ? $_SESSION['pttpilotmode'] : 0;
    $targetandhdg = isset($_SESSION['ptttargetandhdg']) ? $_SESSION['ptttargetandhdg'] : 0;
    $windangle =    isset($_SESSION['pttwindangle']) ? $_SESSION['pttwindangle'] : 0;
    $myWP= $targetlat.",".$targetlong;
    if ($targetandhdg>0) {
        $myWP=$myWP."@".$targetandhdg;
    }

    //helper pour construire la page
    function echoPilototoRow($numline, $row = 0, $ts = "", $pim = "", $pip = "", $status = "") {
        global $pilotmodeList;
        global $pilotmode;
        if ($row === 0) {
            $klasssuffix = "blank";
            $ts = time();
            $firstcolaction = "pilototo_prog_add";
            $statusstring = "";
        } else {
            $klasssuffix = $status;
            $firstcolaction = "pilototo_prog_upd";
            $statusstring = "$status&nbsp;<input type=\"submit\" name=\"action\" value=" . getLocalizedString("pilototo_prog_del") . " />";
        }
        $timestring = gmdate("Y/m/d H:i:s", $ts)." GMT";

        echo "<form action=\"pilototo.php\" method=\"post\" onsubmit=\"if (!validate_pim($numline)) {alert('" . getLocalizedString("pilototo_PIM_error") . "');return(false);}\" >\n";
        echo "  <input type=\"hidden\" name=\"taskid\" value=\"$row\" />\n";
        echo "  <tr class=\"linepilototobox-$klasssuffix\">\n";
        echo "    <td><input type=\"submit\" name=\"action\" value=" . getLocalizedString($firstcolaction)  ." ". (($status=='done') ? 'disabled' :'') ." /></td>\n";
        echo "    <td><input id=\"ts_value_$numline\" type=\"text\" name=\"time\" ". (($status=='done') ? "disabled=\"disabled\"" : "") ." onChange=\"majhrdate($numline);\" width=\"15\" size=\"15\" value=\"$ts\" /></td>\n";
        echo "    <td><img src=\"".DIRECTORY_JSCALENDAR."/img.gif\" id=\"trigger_jscal_$numline\" class=\"calendarbutton\" title=\"Date selector\" onmouseover=\"this.style.background='red';\" onmouseout=\"this.style.background=''\" /></td>\n";
        echo "    <td><input type=\"text\" size=\"22\" width=\"22\" name=\"gmtdate\" disabled=\"disabled\" value=\"" . $timestring . "\" /></td>\n";
        echo "    <td><select onchange=\"checkpip($numline,".(($row === 0) ? "false" : "true").",'".$pim."','".$pip."'); document.forms[$numline].pip.focus(); document.forms[$numline].pip.style.color = '#0000FF';\" name=\"pim\" ". (($status=='done') ? "disabled=\"disabled\"" : "") .">\n";
        for ($i = 1; $i <= count($pilotmodeList); $i++) {
            echo "    <option ";
            if (($i == $pim) or (($row === 0) and ($i == $pilotmode)) ) {
                echo "selected=\"selected\" ";
            }
            echo "value=\"$i\">$i:".getLocalizedString($pilotmodeList[$i])."</option>";
        }
        echo "    </select></td>\n";
        echo "    <td><input type=\"text\" name=\"pip\" ". (($status=='done') ? "disabled=\"disabled\"" : "") ." width=\"30\" size=\"30\" value=\"$pip\" /></td>\n";
        echo "    <td>$statusstring</td>\n";
        //taskid, time, pilotmode, pilotparameter, status .. + Human readable date
        echo "    <td>" . $row . "</td>\n";
        echo "  </tr>\n";
        echo "</form>\n";
    }

    // Les entêtes
    // FIXME : disposer d'un fichier d'en tête commun plus complet !
    include("includes/doctypeheader.html");
    echo "\n<title>".getLocalizedString("VLM Programmable Auto Pilot")."</title>";
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"style/" . getTheme() . "/style.css\" />";

///   CODE JAVASCRIPT
?>
<!-- widget calendrier -->
<script type="text/javascript" src="<?php echo DIRECTORY_JSCALENDAR; ?>/calendar.js"></script>
<script type="text/javascript" src="<?php echo DIRECTORY_JSCALENDAR; ?>/lang/calendar-<?php echo getCurrentLang(); ?>.js"></script>
<script type="text/javascript" src="<?php echo DIRECTORY_JSCALENDAR; ?>/calendar-setup.js"></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?php echo DIRECTORY_JSCALENDAR; ?>/calendar-system.css">

<script type="text/javascript" src="externals/overlib/overlib.js"><!-- overLIB (c) Erik Bosrup --></script>

<script type="text/javascript">
    var calendars = new Array();

    function calbuttonsetup(n) {

        for (i=0;i<=n;i++) {
            calendars[i] = Calendar.setup({
                inputField     :    "ts_value_"+i,     // id of the input field
                ifFormat       :    "%s",      // format of the input field
                button         :    "trigger_jscal_"+i,  // trigger for the calendar (button ID)
                align          :    "Br",           // alignment
                singleClick    :    false,
                showsTime      :    true,
                firstDay       :    1,
                timeFormat     :    "24"
            });
        }
    }

    function dateobj(i) {
        var da = eval(document.forms[i].time.value);
        document.forms[i].time.value = da;
        da*=1000;
        return(new Date(da));
    }

    function majhrdate(i) {
        var d = dateobj(i);
        document.forms[i].gmtdate.value=d.toGMTString();
        //FIXME : Risque de réentrance ?
        //calendars[i].setDate(d);
    }

    function checkpip(i,bmodify,piminit,pipinit) {
        var pilotmode="<?php echo $pilotmode;?>";
        var boatheading = "<?php echo $boatheading;?>";
        var myWP = "<?php echo $myWP;?>";
        var windangle = "<?php echo $windangle;?>";
        var oRed = '#ff0000';
        switch(document.forms[i].pim.value) {
            case "1" : 
                document.forms[i].pip.value = boatheading;
                break;
            case "2" :
                document.forms[i].pip.value = windangle;
                break;
            case "3" : case "4" : case "5" :
                if (!bmodify ||  (piminit == "1" || piminit == "2")) {
                     document.forms[i].pip.value = myWP;
                } else {
                    document.forms[i].pip.value = pipinit;
                }
                break;
        }

        var pim = eval(document.forms[i].pim.value);
        if ( pim >= 3 && pim <= 5 ) {
            document.forms[i].pip.disabled=false;
        } else {
            document.forms[i].pip.disabled=false;
        }
    }

    // ticket#550
    function validate_pim(i) {
        var ordre = document.forms[i].pip.value;
        switch(document.forms[i].pim.value)
        {
            case '1':
                var reg=new RegExp("^([0-9]{1}|[1-9][0-9]|[1-2][0-9][0-9]|3[0-5][0-9])([.]{1}[0-9]{1,5})?$","i");
                break;
            case '2':
                var reg=new RegExp("^[-]?(([1-9][0-9]|[0-9]{1}|1[0-7][0-9])([.]{1}[0-9]{1,5})?|180)$","i");
                break;
            case '3': case'4': case '5' :
                var reg=new RegExp("^[-]?(([0-9]{1}|[1-8]{1}[0-9]{1})([.]{1}[0-9]{1,10})?|90)[,]{1}[-]?(([1-9][0-9]|[0-9]{1}|1[0-7][0-9])([.]{1}[0-9]{1,10})?|180)([@](([0-9]{1}|[1-9][0-9]|[1-2][0-9][0-9]|3[0-5][0-9])([.]{1}[0-9]{1,6})?|([-]1([.][0]{1,6})?)?))?$","i");
        }
		//#562 : don't check grammar if del button : alert("<?php echo getLocalizedString("pilototo_prog_del");?>");
        return((document.activeElement.value == "<?php echo getLocalizedString("pilototo_prog_del");?>" ? true : reg.test(ordre)));
    }

</script>

<?php
    echo "</head><body>";

    // Test si connecté ou pas.
    $idusers = getLoginId() ;
    if ( empty($idusers) ) {
        echo htmlShouldNotDoThat();
        exit();
    }

    echo "<h4>" . getLocalizedString("pilototo_prog_title") . "</h4>" ;
    $usersObj = getLoggedUserObject();

    //echo "<span style=\"font-size:8pt;color:red;\">[debug] pilot:" . $pilotmode . ";windangle:" . $windangle . ";heading:" . $boatheading . ";myWP:" . $myWP . " </span>" ;

    /* PILOTO (class users) Functions
        function pilototoCheck()
        function pilototoList($forcemaster = True)
        function pilototoDelete($taskid)
        function pilototoAdd($time, $pim, $pip)
        function pilototoUpdate($taskid, $time, $pim, $pip)
    */

    $action = get_cgi_var('action');
    $pilotolist_force_master = False;
    if ( !empty($action)) {
        // Action donnée, on exécute l'action
        $pilotolist_force_master = True; // We will need the freshest datas after update, thus we force data fetching from the master
        switch ($action) {
            case getLocalizedString("pilototo_prog_add"):
                $time=quote_smart($_POST['time']);
                $pim=quote_smart($_POST['pim']);
                $pip=quote_smart($_POST['pip']);

                if ( !empty($time) && !(empty($pim)) && ( !empty($pip) || $pip == 0 ))  {
                    if ( $pim <1 || $pim >5) {
                        echo "ERROR ADD : PIM between 1 and 5 please.";
                    } else if ( ( $pim == 1 ) && (!is_numeric($pip) or $pip <0 or $pip >=360)  ) {
                        echo "ERROR ADD : With PIM=1, PIP should be between 0 and 359.9  please";
                    } else if ( ( $pim == 2 ) && (!is_numeric($pip) or $pip <-180 or $pip >180)  ) {
                        echo "ERROR ADD : With PIM=2, PIP should be between -180 and 180 please";
                    } else if (  ( $pim == 3 or $pim == 4 or $pim == 5)
                            &&    ( strlen($pip)==0 or strpos($pip, ',')==false or preg_match("@,.*,@i", $pip) )
                        ) {
                        echo "ERROR ADD : With PIM=3, 4 or 5, PIP should be 0,0 or LATITUDE,LONGITUDE (',' between lat and long, and '.' between units and decimals)";
                    } else {
                        $rc=$usersObj->pilototoAdd(intval($time), intval($pim), $pip);
                    }
                } else {
                    printf ("ERROR ADD: Mandatory Param missing... time=%s, pim=%s, pip=%s\n", $time, $pim, $pip);
                }
                break;
            case getLocalizedString("pilototo_prog_upd"):
                $taskid=quote_smart($_POST['taskid']);
                $time=quote_smart($_POST['time']);
                $pim=quote_smart($_POST['pim']);
                $pip=quote_smart($_POST['pip']);

                if ( !empty($taskid) && !empty($time) && !(empty($pim)) && ( !empty($pip) || $pip ==0 ) ) {
                if ( $pim <1 || $pim >5) {
                    echo "ERROR : PIM between 1 and 5 please.";
                    } else if ( ( $pim == 1 ) && (!is_numeric($pip) or $pip <0 or $pip >=360)  ) {
                        echo "ERROR : With PIM=1, PIP should be between 0 and 359.9 please";
                    } else if ( ( $pim == 2 ) && (!is_numeric($pip) or $pip <-180 or $pip >180)  ) {
                        echo "ERROR : With PIM=2, PIP should be between -180 and 180 please";
                    } else if ( ( $pim == 3 or $pim == 4 or $pim == 5 ) && ( strlen($pip)==0 or strpos($pip, ',')==false )  ) {
                        echo "ERROR : With PIM=3 or 4, PIP should be 0,0 or LATITUDE,LONGITUDE";
                    } else {
                        $rc=$usersObj->pilototoUpdate($taskid, $time, $pim, $pip);
                    }
                } else {
                    printf ("ERROR UPD: Mandatory Param missing... taskid=%d, time=%s, pim=%s, pip=%s\n", $taskid, $time, $pim, $pip);
                }
                break;
            case getLocalizedString("pilototo_prog_del"):
                $taskid=quote_smart($_POST['taskid']);
                if ( !empty($taskid) ) {
                    $rc=$usersObj->pilototoDelete($taskid);
                } else {
                    printf ("ERROR DEL: Task id should not be empty to delete it");
                }
                break;
            default:
        }
    }


    // On affiche la liste des actions
    $rc=$usersObj->pilototoList($pilotolist_force_master);

    $time=time();
    echo "<div id=\"pilototolistbox\"><table class=\"pilotolist\">
         <th>&nbsp</th>
         <th><span onmouseover=\"return overlib('<div class=&quot;infobulle&quot;>".nl2br(getLocalizedString('pilototohelp3')) . "</div>', FULLHTML, HAUTO);\" onmouseout=\"return nd();\">".getLocalizedString("Epoch Time")."</span></th>
         <th></th>
         <th>".getLocalizedString("Human Readable date")."</th>
         <th><span onmouseover=\"return overlib('<div class=&quot;infobulle&quot;>".nl2br(getLocalizedString('pilototohelp4')) . "</div>', FULLHTML, HAUTO);\" onmouseout=\"return nd();\">PIM</span></th>
         <th><span onmouseover=\"return overlib('<div class=&quot;infobulle&quot;>".nl2br(getLocalizedString('pilototohelp5')) . "</div>', FULLHTML, HAUTO);\" onmouseout=\"return nd();\">PIP</span></th>
         <th><span onmouseover=\"return overlib('<div class=&quot;infobulle&quot;>".nl2br(getLocalizedString('pilototohelp6')) . "</div>', FULLHTML, HAUTO);\" onmouseout=\"return nd();\">".getLocalizedString("Status")."</span></th>
         <th>N&deg;</th>\n";
    $numligne=0;
    if ( count($usersObj->pilototo) != 0) {
        foreach ($usersObj->pilototo as $pilototo_row) {
            echoPilototoRow($numligne, $pilototo_row['TID'], $pilototo_row['TTS'], $pilototo_row['PIM'], $pilototo_row['PIP'], $pilototo_row['STS']);
            $numligne++;
        }
    } else {
        echo  "<tr id=\"pilototo-no-event\" class=\"pilototoinfo\"><td  colspan=\"8\">" . getLocalizedString("pilototo_no_event") . "</td></tr>\n" ;
    }
    
    if ( $numligne < PILOTOTO_MAX_EVENTS ) {
        echoPilototoRow($numligne);
        // #542 : focus sur le time de la ligne de ADD, preremplissage de la combo PIM
        echo "<script type=\"text/javascript\">calbuttonsetup($numligne);checkpip($numligne,false,'');document.forms[$numligne].time.focus();</script>\n";
    } else {
        echo "<tr id=\"pilototo-max-event\" class=\"pilototoinfo\">
            <td colspan=8>MAX " . PILOTOTO_MAX_EVENTS . " events</td>
            </tr>\n";
    }
    echo "</table></div>\n";
    echo "<div id=\"helptimepilototobox\">\n";
    echo getLocalizedString("Server(s) time is now")."&nbsp;<b>" . $time  . " (" .gmdate("Y/m/d H:i:s", $time). " GMT)</b><br />\n";
    //echo nl2br(getLocalizedString('pilototohelp2'));
    echo "</div>\n";
    echo "<hr />";
    echo "<div id=\"buttonspilototobox\">\n";
    echo "<input type=\"button\" value=\"Close\" onClick=\"javascript:self.close();\" />\n";
    echo "<input type=\"button\" value=\"Refresh\" onClick=\"javascript:location.reload();\" />\n";
    echo "</div>\n";

    echo "</body></html>";
?>